@php
    $currentUser = auth()->user();

    // Resolve primary service / sub-department for hierarchy drill-down
    $primaryService = null;
    $primarySubDept = null;

    if ($currentUser) {
        // For Admin de cellule / user, capture a primary service (used only for data attributes)
        if ($currentUser->hasRole('Admin de cellule') || $currentUser->hasRole('user')) {
            $primaryService = $currentUser->service ?? $currentUser->services->first();
        }

        // If user has any sub-department assignment (primary or pivot), use it for dashboard drill-down
        $allSubDepts = collect();
        if ($currentUser->subDepartment) {
            $allSubDepts->push($currentUser->subDepartment);
        }
        if ($currentUser->subDepartments && $currentUser->subDepartments->isNotEmpty()) {
            $allSubDepts = $allSubDepts->merge($currentUser->subDepartments);
        }
        if ($allSubDepts->isNotEmpty()) {
            $primarySubDept = $allSubDepts->first();
        }
    }
@endphp

<div class="categories-section"
     data-user-service-id="{{ $primaryService->id ?? '' }}"
     data-user-service-name="{{ $primaryService->name ?? '' }}"
     data-user-sub-department-id="{{ $primarySubDept->id ?? '' }}"
     data-user-sub-department-name="{{ $primarySubDept->name ?? '' }}">
    <div class="section-header">
        <h3 id="categoriesTitle">{{ ui_t('pages.all_departments') }}</h3>
        <div class="d-flex align-items-center">
            <button id="categoriesBackButton" class="btn btn-sm btn-outline-secondary me-2" style="display: none;" onclick="goBackToCategoriesDepartments()">
                <i class="fas fa-arrow-left me-1"></i> {{ ui_t('pages.back_to_departments') }}
            </button>
        <a href="{{ route('categories.index') }}" class="view-all"
        >{{ ui_t('pages.view_all') }} <i class="fa-solid fa-angle-right"></i
            ></a>
    </div>
    </div>

    <!-- Departments View -->
    <div id="departmentsView" class="row archive gy-3">
        @php
            $user = auth()->user();
            $userDepartments = $user->departments ?? collect();
            $userSubDepts    = $user->subDepartments ?? collect();

            // Get the HomeController instance to access getVisibleDocumentsQuery
            $homeController = app(\App\Http\Controllers\HomeController::class);
            $visibleDocsQuery = $homeController->getVisibleDocumentsQuery();

            // Only "master" and "Super Administrator" should see all departments on the dashboard
            $isGlobalAdmin = $user && ($user->hasRole('master') || $user->hasRole('Super Administrator'));

            // Department-level administrators:
            //  - legacy "Department Administrator"
            //  - canonical "Admin de pole" (pole admin)
            $isDepartmentAdmin = $user && (
                $user->hasRole('Department Administrator') ||
                $user->hasRole('Admin de pole')
            );

            // Service-level users (Admin de cellule / service user) may have multiple services
            // NOTE: Admin de pole is NOT treated as service-scoped; they see department -> sub-dept -> service.
            $isServiceScoped = $user && ($user->hasRole('Admin de cellule') || $user->hasRole('user'));

            // IMPORTANT: For service-scoped users, we want to show their services first,
            // even if they are also attached to a sub-department.
            if ($isServiceScoped && ($user->services && $user->services->isNotEmpty())) {
                // Service Manager / Service User: show all assigned services
                $userServices = $user->services;

                // Include primary service_id if present but not already in the relation
                if ($user->service_id && ! $userServices->pluck('id')->contains($user->service_id)) {
                    $primaryServiceModel = \App\Models\Service::find($user->service_id);
                    if ($primaryServiceModel) {
                        $userServices = $userServices->push($primaryServiceModel);
                    }
                }

                $departments = $userServices->unique('id')->map(function ($service) use ($visibleDocsQuery) {
                    // Use cloned visible documents query to respect permissions
                    $documentCount = (clone $visibleDocsQuery)->where('service_id', $service->id)->count();

                    return (object) [
                        'id' => $service->id,
                        'name' => $service->name,
                        'documents_count' => $documentCount,
                        'type' => 'service',
                    ];
                })
                ->sortByDesc('documents_count')
                ->take(4);
            } elseif ($userSubDepts->isNotEmpty() && ! $isGlobalAdmin) {
                // Sub-department admin: show exactly their assigned sub-departments
                $departments = $userSubDepts->map(function ($subDept) use ($visibleDocsQuery) {
                    // Count documents in all services under this sub-department
                    $serviceIds = $subDept->services->pluck('id');
                    $documentCount = $serviceIds->isEmpty()
                        ? 0
                        : (clone $visibleDocsQuery)->whereIn('service_id', $serviceIds)->count();

                    return (object) [
                        'id' => $subDept->id,
                        'name' => $subDept->name,
                        'documents_count' => $documentCount,
                        'type' => 'sub_department',
                    ];
                })
                ->sortByDesc('documents_count')
                ->take(4);
            } elseif ($isServiceScoped && ($user->services && $user->services->isNotEmpty())) {
                // Service Manager / Service User: show all assigned services
                $userServices = $user->services;

                // Include primary service_id if present but not already in the relation
                if ($user->service_id && ! $userServices->pluck('id')->contains($user->service_id)) {
                    $primaryServiceModel = \App\Models\Service::find($user->service_id);
                    if ($primaryServiceModel) {
                        $userServices = $userServices->push($primaryServiceModel);
                    }
                }

                $departments = $userServices->unique('id')->map(function ($service) use ($visibleDocsQuery) {
                    // Use cloned visible documents query to respect permissions
                    $documentCount = (clone $visibleDocsQuery)->where('service_id', $service->id)->count();

                    return (object) [
                        'id' => $service->id,
                        'name' => $service->name,
                        'documents_count' => $documentCount,
                        'type' => 'service',
                    ];
                })
                ->sortByDesc('documents_count')
                ->take(4);
            } elseif ($isGlobalAdmin || $isDepartmentAdmin || $userDepartments->count() > 1) {
                // Show departments (top-level for global admins, department admins, or multi-department users)
                if ($isGlobalAdmin) {
                    // Global admins: top 4 departments across the whole system
                    $departments = \App\Models\Department::withCount('documents')
                        ->orderBy('documents_count', 'desc')
                        ->take(4)
                        ->get()
                        ->map(function ($dept) {
                            $dept->type = 'department';
                            return $dept;
                        });
                } else {
                    // Non-global users (e.g. Department Administrators): only their assigned departments
                    $departments = $userDepartments->map(function ($dept) use ($visibleDocsQuery) {
                        $documentCount = (clone $visibleDocsQuery)->where('department_id', $dept->id)->count();
                        return (object) [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'documents_count' => $documentCount,
                            'type' => 'department',
                        ];
                    })
                    ->sortByDesc('documents_count')
                    ->take(4);
                }
            } else {
                $departments = collect();
            }
        @endphp

        @if($departments->count() > 0)
            @php
                $colors = [
                    ['bar' => '#f0d672', 'dot' => 'yellow-pending','icon' => 'assets/Group 634.svg' ], // Yellow
                    ['bar' => '#e63946', 'dot' => 'red-pending','icon' => 'assets/Group 6.svg'],    // Red
                    ['bar' => '#47a778', 'dot' => 'green-pending','icon' => 'assets/Group 8.svg'],  // Green
                    ['bar' => '#68a0fd', 'dot' => 'blue-pending','icon' => 'assets/Clip path group.svg'],   // Blue 
                ];
            @endphp

            @foreach($departments as $i => $department)
                @php
                    $color = $colors[$i % count($colors)];

                    $itemType = $department->type ?? 'department';
                    $itemClass = match($itemType) {
                        'sub_department' => 'sub-department-item',
                        'service'        => 'service-item',
                        default          => 'department-item',
                    };

                    // Pending count must respect the actual hierarchy type
                    if ($itemType === 'sub_department') {
                        // Find the matching sub-department and count docs in its services
                        $subDeptModel = $userSubDepts->firstWhere('id', $department->id);
                        if ($subDeptModel) {
                            $serviceIds = $subDeptModel->services->pluck('id');
                            $pendingCount = $serviceIds->isEmpty()
                                ? 0
                                : (clone $visibleDocsQuery)->whereIn('service_id', $serviceIds)
                                    ->where('status', 'pending')
                                    ->count();
                        } else {
                            $pendingCount = 0;
                        }
                    } elseif ($itemType === 'service') {
                        // Service item: count directly by service_id
                        $pendingCount = (clone $visibleDocsQuery)->where('service_id', $department->id)
                            ->where('status', 'pending')
                            ->count();
                    } else {
                        // Department item (default): count by department_id
                        $pendingCount = (clone $visibleDocsQuery)->where('department_id', $department->id)
                            ->where('status', 'pending')
                            ->count();
                    }
                @endphp

                <div class="col-md-6">
                    <div class="border p-4 rounded-2 category-item-hover {{ $itemClass }}" 
                         @if($itemType === 'sub_department')
                             data-sub-department-id="{{ $department->id }}" 
                             data-sub-department-name="{{ $department->name }}"
                         @elseif($itemType === 'service')
                             data-service-id="{{ $department->id }}" 
                             data-service-name="{{ $department->name }}"
                         @else
                             data-department-id="{{ $department->id }}" 
                             data-department-name="{{ $department->name }}"
                         @endif
                         style="cursor: pointer;">
                        <div class="d-flex justify-content-between">
                            <img src="{{ asset($color['icon']) }}" alt="department" />
                            <div class="d-flex">
                                <h5 class="d-flex mt-1">
                                    <div class="color-pending {{ $color['dot'] }} me-2 mt-1"></div>
                                    {{ ui_t('pages.stats.pending') }}
                                </h5>
                                <p class="ms-2">{{ $pendingCount }}</p>
                            </div>
                        </div>

                        <h3 class="mt-3">{{ $department->name }}</h3>
                        <div class="d-flex align-items-center mt-2">
                            <div
                                class="progress"
                                style="
                            width: 100%;
                            height: 8px;
                            background-color: #eee;
                            border-radius: 10px;
                        "
                            >
                                <div
                                    class="progress-bar"
                                    role="progressbar"
                                    style="
                                width: 100%;
                                border-radius: 10px;
                                background-color: {{ $color['bar'] }};
                            "
                                    aria-valuenow="100"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                ></div>
                            </div>
                            <span class="ms-2">{{ $department->documents_count }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif(! $primarySubDept)
            <!-- Show categories directly if user has only one department (and user is not sub-department scoped) -->
            @php
                $departmentId = $userDepartments->first()?->id;
                if ($departmentId) {
                    $categories = \App\Models\Category::where('department_id', $departmentId)
                        ->withCount([
                            'documents as pending_count' => fn($q) => $q->where('status', 'pending'),
                            'documents as total_count',
                        ])
                        ->orderBy('total_count', 'desc')
                        ->take(4)
                        ->get();
                } else {
                    $categories = collect();
                }
            @endphp

            @if($categories->count() > 0)
        @php
            $colors = [
                ['bar' => '#f0d672', 'dot' => 'yellow-pending','icon' => 'assets/Group 634.svg' ], // Yellow
                ['bar' => '#e63946', 'dot' => 'red-pending','icon' => 'assets/Group 6.svg'],    // Red
                ['bar' => '#47a778', 'dot' => 'green-pending','icon' => 'assets/Group 8.svg'],  // Green
                ['bar' => '#68a0fd', 'dot' => 'blue-pending','icon' => 'assets/Clip path group.svg'],   // Blue 
            ];
        @endphp

        @foreach($categories as $i => $category)
            @php
                $color = $colors[$i % count($colors)];
            @endphp

                    <div class="col-md-6">
                <a href="{{ route('categories.subcategories', $category) }}" class="text-decoration-none text-reset">
                <div class="border p-4 rounded-2 category-item-hover">
                    <div class="d-flex justify-content-between">
                        <img src="{{ asset($color['icon']) }}" alt="folder" />
                        <div class="d-flex">
                            <h5 class="d-flex mt-1">
                                <div class="color-pending {{ $color['dot'] }} me-2 mt-1"></div>
                                {{ ui_t('pages.stats.pending') }}
                            </h5>
                            <p class="ms-2">{{ $category->pending_count }}</p>
                        </div>
                    </div>

                    <h3 class="mt-3">{{ $category->name }}</h3>
                    <div class="d-flex align-items-center mt-2">
                        <div
                            class="progress"
                            style="
                        width: 100%;
                        height: 8px;
                        background-color: #eee;
                        border-radius: 10px;
                    "
                        >
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="
                            width: 100%;
                            border-radius: 10px;
                            background-color: {{ $color['bar'] }};
                        "
                                aria-valuenow="100"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                        <span class="ms-2">{{ $category->total_count }}</span>
                    </div>
                </div>
                </a>
            </div>
        @endforeach
            @else
                <div class="col-12">
                    <div class="text-center py-4">
                        <p class="text-muted">{{ ui_t('pages.no_categories') }}</p>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Categories View (shown by default when user has a primary sub-department) -->
    <div id="categoriesView" style="display: none;">
        <div class="row archive gy-3" id="categoriesGrid">
            <!-- Categories will be loaded here via JavaScript -->
        </div>
    </div>

    <!-- Pagination (only show for categories view) -->
    <div id="categoriesPagination" class="mt-4" style="display: none;">
        <!-- Pagination will be loaded here via JavaScript -->
   </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.categories-section');
    const userServiceId = container?.dataset.userServiceId;
    const userServiceName = container?.dataset.userServiceName;
    const userSubDeptId = container?.dataset.userSubDepartmentId;
    const userSubDeptName = container?.dataset.userSubDepartmentName;

    // Default: handle hierarchy clicks
    // Default: handle hierarchy clicks
    // - If the card has a sub-department id, go to its services
    // - If the card has a service id, go directly to its categories
    // - Otherwise, treat it as a department card and go to its sub-departments
    document.querySelectorAll('.department-item, .sub-department-item, .service-item').forEach(item => {
        item.addEventListener('click', function() {
            const subDeptId = this.dataset.subDepartmentId;
            if (subDeptId) {
                const subDeptName = this.dataset.subDepartmentName || '';
                loadServicesForSubDepartment(subDeptId, subDeptName);
                return;
            }

            const serviceId = this.dataset.serviceId;
            if (serviceId) {
                const serviceName = this.dataset.serviceName || '';
                loadCategoriesForService(serviceId, serviceName);
                return;
            }

            const departmentId = this.dataset.departmentId;
            if (departmentId) {
                const departmentName = this.dataset.departmentName || '';
                loadSubDepartmentsForDepartment(departmentId, departmentName);
            }
        });
    });
});

// Localized strings for JS
const T = {
    loading: @json(ui_t('pages.loading')),
    categories_in: @json(ui_t('pages.categories_in')),
    error_loading_categories: @json(ui_t('pages.error_loading_categories')),
    no_categories_in_dept: @json(ui_t('pages.no_categories_in_dept')),
    total: @json(ui_t('pages.total')),
    all_departments: @json(ui_t('pages.all_departments')),
};

function updateHierarchyTitle(subtitle) {
    document.getElementById('categoriesTitle').textContent = subtitle;
    document.getElementById('categoriesBackButton').style.display = 'inline-block';
}

function showHierarchyView() {
    document.getElementById('departmentsView').style.display = 'none';
    document.getElementById('categoriesView').style.display = 'block';
}

function loadSubDepartmentsForDepartment(departmentId, departmentName) {
    // Show loading state
    document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">' + T.loading + '</span></div></div></div>';

    fetch(`/departments/${departmentId}/sub-departments`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }

            updateHierarchyTitle(data.subtitle || data.title);
            showHierarchyView();
            renderHierarchyItems(data.data, 'sub_department');
        })
        .catch(error => {
            console.error('Error loading sub-departments:', error);
            document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><p class="text-danger">' + T.error_loading_categories + '</p></div></div>';
        });
}

function loadServicesForSubDepartment(subDepartmentId, subDepartmentName) {
    document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">' + T.loading + '</span></div></div></div>';

    fetch(`/sub-departments/${subDepartmentId}/services`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }

            updateHierarchyTitle(data.subtitle || data.title);
            showHierarchyView();
            renderHierarchyItems(data.data, 'service');
        })
        .catch(error => {
            console.error('Error loading services:', error);
            document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><p class="text-danger">' + T.error_loading_categories + '</p></div></div>';
        });
}

function loadCategoriesForService(serviceId, serviceName) {
    document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">' + T.loading + '</span></div></div></div>';

    fetch(`/services/${serviceId}/categories`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }

            updateHierarchyTitle(data.subtitle || data.title);
            showHierarchyView();
            updateHierarchyTitle(data.subtitle || data.title);
            showHierarchyView();
            renderCategories(data.data, data.service_id);
        })
        .catch(error => {
            console.error('Error loading categories by service:', error);
            document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><p class="text-danger">' + T.error_loading_categories + '</p></div></div>';
        });
}

function loadCategoriesForDepartment(departmentId, departmentName) {
    // Show loading state
    document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">' + T.loading + '</span></div></div></div>';
    
    // Fetch categories for the department
    fetch(`/categories-by-department/${departmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            
            // Update title
            document.getElementById('categoriesTitle').textContent = `${T.categories_in} ${departmentName}`;
            
            // Show back button
            document.getElementById('categoriesBackButton').style.display = 'inline-block';
            
            // Hide departments view, show categories view
            document.getElementById('departmentsView').style.display = 'none';
            document.getElementById('categoriesView').style.display = 'block';
            
            // Render categories
            // Render categories
            renderCategories(data.data, null);
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            document.getElementById('categoriesGrid').innerHTML = '<div class="col-12"><div class="text-center py-4"><p class="text-danger">' + T.error_loading_categories + '</p></div></div>';
        });
}

function renderHierarchyItems(items, level) {
    const colors = [
        {bar: '#f0d672', dot: 'yellow-pending', icon: 'assets/Group 634.svg'},
        {bar: '#e63946', dot: 'red-pending', icon: 'assets/Group 6.svg'},
        {bar: '#47a778', dot: 'green-pending', icon: 'assets/Group 8.svg'},
        {bar: '#68a0fd', dot: 'blue-pending', icon: 'assets/Clip path group.svg'},
    ];

    let html = '';

    if (items.length === 0) {
        html = '<div class="col-12"><div class="text-center py-4"><p class="text-muted">' + T.no_categories_in_dept + '</p></div></div>';
    } else {
        items.forEach((item, index) => {
            const color = colors[index % colors.length];
            let onClick;
            if (level === 'sub_department') {
                onClick = `onclick=\"loadServicesForSubDepartment(${item.id}, '${item.name.replace(/'/g, "&#39;")}')\"`;
            } else if (level === 'service') {
                onClick = `onclick=\"loadCategoriesForService(${item.id}, '${item.name.replace(/'/g, "&#39;")}')\"`;
            } else {
                onClick = '';
            }

            html += `
                <div class="col-md-6">
                    <div class="border p-4 rounded-2 category-item-hover" style="cursor:pointer;" ${onClick}>
                        <div class="d-flex justify-content-between">
                            <img src="/${color.icon}" alt="folder" />
                            <div class="d-flex">
                                <h5 class="d-flex mt-1">
                                    <div class="color-pending ${color.dot} me-2 mt-1"></div>
                                    ${T.total}
                                </h5>
                                <p class="ms-2">${item.count}</p>
                            </div>
                        </div>
                        <h3 class="mt-3">${item.name}</h3>
                        <div class="d-flex align-items-center mt-2">
                            <div class="progress" style="width: 100%; height: 8px; background-color: #eee; border-radius: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: 100%; border-radius: 10px; background-color: ${color.bar};" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ms-2">${item.count}</span>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    document.getElementById('categoriesGrid').innerHTML = html;
}

function renderCategories(categories, serviceId) {
    console.log('Rendering categories:', categories); // Debug log
    
    const colors = [
        {bar: '#f0d672', dot: 'yellow-pending', icon: 'assets/Group 634.svg'},
        {bar: '#e63946', dot: 'red-pending', icon: 'assets/Group 6.svg'},
        {bar: '#47a778', dot: 'green-pending', icon: 'assets/Group 8.svg'},
        {bar: '#68a0fd', dot: 'blue-pending', icon: 'assets/Clip path group.svg'},
    ];
    
    let html = '';
    
    if (categories.length === 0) {
        html = '<div class="col-12"><div class="text-center py-4"><p class="text-muted">' + T.no_categories_in_dept + '</p></div></div>';
    } else {
        categories.forEach((category, index) => {
            const color = colors[index % colors.length];
            html += `
                <div class="col-md-6">
                    <a href="/documents/by-category/${category.id}?show_expired=1" class="text-decoration-none text-reset">
                        <div class="border p-4 rounded-2 category-item-hover">
                            <div class="d-flex justify-content-between">
                                <img src="/${color.icon}" alt="folder" />
                                <div class="d-flex">
                                    <h5 class="d-flex mt-1">
                                        <div class="color-pending ${color.dot} me-2 mt-1"></div>
                                        ${T.total}
                                    </h5>
                                    <p class="ms-2">${category.count}</p>
                                </div>
                            </div>
                            <h3 class="mt-3">${category.name}</h3>
                            <div class="d-flex align-items-center mt-2">
                                <div class="progress" style="width: 100%; height: 8px; background-color: #eee; border-radius: 10px;">
                                    <div class="progress-bar" role="progressbar" style="width: 100%; border-radius: 10px; background-color: ${color.bar};" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="ms-2">${category.count}</span>
                            </div>
                        </div>
                    </a>
                </div>
            `;
        });
    }
    
    console.log('Generated HTML:', html); // Debug log
    // Set the HTML content
    document.getElementById('categoriesGrid').innerHTML = html;
}

function goBackToCategoriesDepartments() {
    console.log('Categories back button clicked!'); // Debug log
    
    // Reset title
    document.getElementById('categoriesTitle').textContent = T.all_departments;
    
    // Hide back button
    document.getElementById('categoriesBackButton').style.display = 'none';
    
    // Show departments view, hide categories view
    document.getElementById('departmentsView').style.display = '';
    document.getElementById('categoriesView').style.display = 'none';
    document.getElementById('categoriesPagination').style.display = 'none';
}
</script>
