<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Service;
use App\Models\SubDepartment;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class DocumentSearchService
{
    /**
     * Search documents using Elasticsearch with consistent results
     */
    public static function searchDocuments(string $query = '', array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        // Step 1: Basic Elasticsearch search
        $searchResults = self::performElasticsearchSearch($query);
        
        // Step 2: Apply permission filtering
        $searchResults = self::applyPermissionFilter($searchResults);
        
        // Step 3: Apply additional filters
        $searchResults = self::applyFilters($searchResults, $filters);
        
        // Step 4: Convert to Document models and paginate
        return self::paginateResults($searchResults, $perPage, $page);
    }

    /**
     * Search documents by category using Elasticsearch
     */
    public static function searchByCategory(int $categoryId, string $query = '', array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        // Add category filter
        $filters['category_id'] = $categoryId;
        
        return self::searchDocuments($query, $filters, $perPage, $page);
    }

    /**
     * Search document versions using Elasticsearch
     */
    public static function searchVersions(string $query = '', array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        // Step 1: Basic Elasticsearch search
        $searchResults = self::performElasticsearchSearch($query);
        
        // Step 2: Apply permission filtering
        $searchResults = self::applyPermissionFilter($searchResults);
        
        // Step 3: Apply additional filters
        $searchResults = self::applyFilters($searchResults, $filters);
        
        // Step 4: Convert to DocumentVersion models and paginate
        return self::paginateVersionResults($searchResults, $perPage, $page);
    }

    /**
     * Get statistics for reports using Elasticsearch
     */
    public static function getStatistics(array $filters = []): array
    {
        // Step 1: Basic Elasticsearch search
        $searchResults = self::performElasticsearchSearch('*');
        
        // Step 2: Apply permission filtering
        $searchResults = self::applyPermissionFilter($searchResults);
        
        // Step 3: Apply additional filters
        $searchResults = self::applyFilters($searchResults, $filters);
        
        // Step 4: Generate statistics
        return self::generateStatistics($searchResults);
    }

    /**
     * Perform basic Elasticsearch search
     */
    private static function performElasticsearchSearch(string $query): Collection
    {
        if (strlen($query) < 2 && $query !== '*') {
            return collect();
        }

        try {
            $builder = DocumentVersion::search($query ?: '*');
            $searchResults = $builder->get();
        } catch (\Throwable $e) {
            Log::warning('Elasticsearch search failed, falling back to empty result set', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }

        // Load related models for filtering
        $searchResults->load([
            'uploadedBy', 
            'document.tags', 
            'document.department', 
            'document.subcategory.category', 
            'document.physicalLocation', 
            'document.createdBy',
            'document.auditLogs.user',
            'document.box.shelf.row.room'
        ]);

        return $searchResults;
    }

    /**
     * Apply permission filtering (same logic as DocumentElasticSearch)
     */
    private static function applyPermissionFilter(Collection $searchResults): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $isSuper = $user->hasRole('master')
            || $user->hasRole('Super Administrator')
            || $user->hasRole('super administrator')
            || $user->hasRole('super_admin');

        // Precompute strict visibility for Division Chief (sub-department admin)
        $divisionChiefDeptIds = collect();
        $divisionChiefServiceIds = collect();

        if ($user->hasRole('Division Chief')) {
            $divisionChiefDeptIds = ($user->relationLoaded('departments') || method_exists($user, 'departments'))
                ? $user->departments->pluck('id')->filter()
                : collect();

            $userSubDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $userSubDeptIds = $userSubDeptIds->unique()->filter();

            if ($divisionChiefDeptIds->isNotEmpty() && $userSubDeptIds->isNotEmpty()) {
                $allowedSubDeptIds = SubDepartment::whereIn('id', $userSubDeptIds)
                    ->whereIn('department_id', $divisionChiefDeptIds)
                    ->pluck('id');

                if ($allowedSubDeptIds->isNotEmpty()) {
                    $divisionChiefServiceIds = Service::whereIn('sub_department_id', $allowedSubDeptIds)
                        ->pluck('id')
                        ->unique()
                        ->filter();
                }
            }
        }

        return $searchResults->filter(function ($docVersion) use ($user, $divisionChiefDeptIds, $divisionChiefServiceIds, $isSuper) {
            $document = $docVersion->document;

            if (! $document) {
                return false;
            }

            // Hide destroyed documents for everyone except Master / Super Admin
            if (! $isSuper && in_array($document->status, ['destroyed'], true)) {
                return false;
            }

            // Super roles can see everything that passes status filter above
            if ($isSuper) {
                return true;
            }

            // If user can view any document, they can see all remaining document versions
            if ($user->can('view any document')) {
                return true;
            }

            // Strict rule for Division Chief: must match both department AND one of
            // the services under their assigned sub-departments. No broader fallback.
            if ($user->hasRole('Division Chief')) {
                if ($divisionChiefDeptIds->isEmpty() || $divisionChiefServiceIds->isEmpty()) {
                    return false;
                }

                return $document
                    && $divisionChiefDeptIds->contains($document->department_id)
                    && $document->service_id
                    && $divisionChiefServiceIds->contains($document->service_id);
            }

            // If user can view service documents, check if document is within any
            // of their visible services (including via category hierarchy).
            if ($user->can('view service document')) {
                $visibleServiceIds = collect();

                if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                    $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
                }

                $subDeptIds = collect();
                if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                    $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
                }
                $subDeptIds = $subDeptIds->unique()->filter();

                if ($subDeptIds->isNotEmpty()) {
                    $visibleServiceIds = $visibleServiceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }

                $visibleServiceIds = $visibleServiceIds->unique()->filter();

                if (! $visibleServiceIds->isEmpty()) {
                    $serviceIds = $visibleServiceIds->all();

                    $docServiceId = $document->service_id;
                    // Prefer direct category.service_id, fall back to subcategory->category if needed
                    $categoryServiceId = $document->category?->service_id
                        ?? $document->subcategory?->category?->service_id;

                    if (($docServiceId && in_array($docServiceId, $serviceIds)) ||
                        ($categoryServiceId && in_array($categoryServiceId, $serviceIds))) {
                        return true;
                    }
                }
            }

            // If user can view department documents, check if document is in their departments
            if ($user->can('view department document')) {
                $departmentIds = $user->departments->pluck('id')->toArray();
                if (!empty($departmentIds) && in_array($document->department_id, $departmentIds)) {
                    return true;
                }
            }

            // If user can view own documents, check if they created the document
            if ($user->can('view own document') && $document->created_by === $user->id) {
                return true;
            }

            // No permission to view this document version
            return false;
        });
    }

    /**
     * Apply additional filters
     */
    private static function applyFilters(Collection $searchResults, array $filters): Collection
    {
        return $searchResults->filter(function ($docVersion) use ($filters) {
            // FILTER: Status
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                if ($docVersion->document->status !== $filters['status']) {
                    return false;
                }
            }

            // FILTER: Category
            // Prefer the document.category_id column (kept in sync), but also
            // fall back to the subcategory->category_id for older data.
            if (!empty($filters['category_id'])) {
                $doc = $docVersion->document;
                $categoryId = $filters['category_id'];
                $directCategoryId = $doc->category_id ?? null;
                $viaSubcategoryId = $doc->subcategory?->category_id ?? null;

                if ($directCategoryId != $categoryId && $viaSubcategoryId != $categoryId) {
                    return false;
                }
            }

            // FILTER: Subcategory
            if (!empty($filters['subcategory_id'])) {
                if ($docVersion->document->subcategory_id != $filters['subcategory_id']) {
                    return false;
                }
            }

            // FILTER: Multiple Subcategories
            if (!empty($filters['subcategory_ids'])) {
                if (!in_array($docVersion->document->subcategory_id, $filters['subcategory_ids'])) {
                    return false;
                }
            }

            // FILTER: Department
            if (!empty($filters['department_id'])) {
                if ($docVersion->document->department_id != $filters['department_id']) {
                    return false;
                }
            }

            // FILTER: Multiple Departments (used to scope reports to user's departments)
            if (!empty($filters['department_ids'])) {
                $deptIds = is_array($filters['department_ids']) ? $filters['department_ids'] : [$filters['department_ids']];
                if (!in_array($docVersion->document->department_id, $deptIds)) {
                    return false;
                }
            }

            // FILTER: Services (used for sub-department filter in reports)
            if (!empty($filters['service_ids'])) {
                $serviceIds = is_array($filters['service_ids']) ? $filters['service_ids'] : [$filters['service_ids']];
                if (!in_array($docVersion->document->service_id, $serviceIds)) {
                    return false;
                }
            }

            // FILTER: File Type (stored as logical category: pdf, doc, image, excel, video, audio, other)
            if (!empty($filters['file_type'])) {
                if ($docVersion->file_type !== $filters['file_type']) {
                    return false;
                }
            }

            // FILTER: Date Range
            if (!empty($filters['date_from'])) {
                if ($docVersion->document->created_at < $filters['date_from']) {
                    return false;
                }
            }

            if (!empty($filters['date_to'])) {
                if ($docVersion->document->created_at > $filters['date_to']) {
                    return false;
                }
            }

            // FILTER: Author
            if (!empty($filters['author'])) {
                $authorFilter = strtolower($filters['author']);
                $fullName = strtolower($docVersion->uploadedBy->full_name ?? '');
                $email = strtolower($docVersion->uploadedBy->email ?? '');
                $metadataAuthor = strtolower($docVersion->metadata['author'] ?? '');

                if (!str_contains($fullName, $authorFilter) &&
                    !str_contains($email, $authorFilter) &&
                    !str_contains($metadataAuthor, $authorFilter)) {
                    return false;
                }
            }

            // FILTER: Keywords
            if (!empty($filters['keywords'])) {
                $filterKeywords = array_filter(array_map('trim', explode(',', strtolower($filters['keywords']))));
                $haystack = strtolower(($docVersion->ocr_text ?? '') . ' ' . ($docVersion->document->title ?? ''));
                foreach ($filterKeywords as $keyword) {
                    if (stripos($haystack, $keyword) === false) {
                        return false;
                    }
                }
            }

            // FILTER: Tags
            if (!empty($filters['tags'])) {
                $filterTags = array_filter(array_map('trim', explode(',', strtolower($filters['tags']))));
                $docTags = $docVersion->document->tags->pluck('name')->map(fn($t) => strtolower($t))->toArray();
                if (!collect($filterTags)->every(fn($tag) => in_array($tag, $docTags))) {
                    return false;
                }
            }

            // FILTER: Favorites Only
            if (!empty($filters['favorites_only'])) {
                $isFavorited = $docVersion->document->favoritedByUsers->contains(auth()->id());
                if (!$isFavorited) {
                    return false;
                }
            }

            // FILTER: Box (Physical Location)
            if (!empty($filters['box_id'])) {
                $boxIds = is_array($filters['box_id']) ? $filters['box_id'] : [$filters['box_id']];
                if (!in_array($docVersion->document->box_id, $boxIds)) {
                    return false;
                }
            }

            // FILTER: Document ID
            if (!empty($filters['document_id'])) {
                if ($docVersion->document_id != $filters['document_id']) {
                    return false;
                }
            }

            // FILTER: Created By
            if (!empty($filters['created_by'])) {
                if ($docVersion->document->created_by != $filters['created_by']) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Paginate results and convert to Document models
     */
    private static function paginateResults(Collection $searchResults, int $perPage, int $page): LengthAwarePaginator
    {
        $total = $searchResults->count();
        $offset = ($page - 1) * $perPage;
        
        // Get unique documents from the search results
        $documents = $searchResults->map(function ($docVersion) {
            return $docVersion->document;
        })->unique('id')->values();
        
        $items = $documents->slice($offset, $perPage)->values();
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Paginate results and return DocumentVersion models
     */
    private static function paginateVersionResults(Collection $searchResults, int $perPage, int $page): LengthAwarePaginator
    {
        $total = $searchResults->count();
        $offset = ($page - 1) * $perPage;
        
        $items = $searchResults->slice($offset, $perPage)->values();
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Generate statistics from search results
     */
    private static function generateStatistics(Collection $searchResults): array
    {
        $documents = $searchResults->map(function ($docVersion) {
            return $docVersion->document;
        })->unique('id');

        return [
            'total_documents' => $documents->count(),
            'by_status' => $documents->groupBy('status')->map->count(),
            'by_department' => $documents->groupBy('department.name')->map->count(),
            'by_category' => $documents->groupBy('category.name')->map->count(),
            'by_file_type' => $searchResults->groupBy(function ($docVersion) {
                return strtolower(pathinfo($docVersion->file_path, PATHINFO_EXTENSION));
            })->map->count(),
        ];
    }
}
