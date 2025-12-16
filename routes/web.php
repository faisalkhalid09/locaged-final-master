<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentDestructionRequestController;
use App\Http\Controllers\DocumentMovementController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\OcrJobController;
use App\Http\Controllers\PhysicalLocationController;
use App\Http\Controllers\UiTranslationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkFlowRuleController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->group(function () {

    // Inline preview for temporary uploads (images/PDFs)
    Route::get('/preview/temp/{token}', [\App\Http\Controllers\FilePreviewController::class, 'temp'])
        ->name('preview.temp');
    Route::post('/toggle-rtl', [\App\Http\Controllers\HomeController::class,'toggleRtl'])->name('toggle.rtl');
    Route::get('/categories-by-department/{departmentId}', [\App\Http\Controllers\HomeController::class,'getCategoriesByDepartment'])->name('categories.by.department');
    Route::get('/departments/{departmentId}/sub-departments', [\App\Http\Controllers\HomeController::class,'getSubDepartmentsByDepartment'])->name('dashboard.sub-departments.by-department');
    Route::get('/sub-departments/{subDepartmentId}/services', [\App\Http\Controllers\HomeController::class,'getServicesBySubDepartment'])->name('dashboard.services.by-sub-department');
    Route::get('/services/{serviceId}/categories', [\App\Http\Controllers\HomeController::class,'getCategoriesByService'])->name('dashboard.categories.by-service');


    Route::get('/broadcast',function () {
        $user = auth()->user();
        if (!$user) {
            return response('Unauthorized', 401);
        }

        $data = [
            'type' => 'info',
            'title' => 'Test Notification',
            'body' => 'This is a fake notification for testing.',
            'action' => 'test',
            'documentId' => null,
            'latestVersionId' => null,
            'icon' => 'info-circle',
        ];

        event(new \App\Events\NotificationBroadcast($user->id, $data));

        return 'Notification broadcasted to user ' . $user->id;
    });


    // UiTranslation
    Route::post('ui-translations/changeLocale', [UiTranslationController::class, 'changeLocale'])->name('ui-translations.changeLocale');
    Route::post('ui-translations/branding', [UiTranslationController::class, 'brandingUpdate'])->name('ui-translations.branding');

    Route::get('/',[\App\Http\Controllers\HomeController::class,'index'])->name('home');

    Route::get('/documents/upload-success', function () {
        return view('documents.step3-send');
    })->name('documents.success');

    Route::get('/notifications', [\App\Http\Controllers\HomeController::class, 'notifications'])->name('notifications');

    // Approvals page: access controlled by policy (approve/decline Document)
    Route::get('/documents/status',[DocumentController::class,'showStatus'])->name('documents.status');


    // Debug route removed for security - see audit report
    // Original route /debug-service/{id} exposed internal database structure

    Route::group(['middleware' => ['role:master|Super Administrator|Admin de pole|Admin de departments|Admin de cellule']], function () {
        Route::get('/users/audit',[UserController::class,'audit'])->name('users.audit');
        Route::get('/users/logs',[UserController::class,'logs'])->name('users.logs');
        Route::get('/users/{id}/activity',[UserController::class,'activity'])->name('user.activity');
        Route::get('/reports', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [\App\Http\Controllers\ReportsController::class, 'export'])->name('reports.export');
        Route::get('/physical-locations/export', [PhysicalLocationController::class, 'export'])->name('physical-locations.export');
        Route::get('/physical-locations/{physicalLocation}/export-files', [PhysicalLocationController::class, 'exportFiles'])->name('physical-locations.export-files');
        Route::resource('physical-locations', PhysicalLocationController::class);

        // New export routes
        Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('/documents/export', [DocumentController::class, 'export'])->name('documents.export');

        Route::resource('users', UserController::class);
    });

    // Structure management (Master & Super Admin & Admin)
    Route::group(['middleware' => ['role:master|Super Administrator|admin']], function () {
        Route::resources([
            'departments' => \App\Http\Controllers\DepartmentController::class,
            'sub-departments' => \App\Http\Controllers\SubDepartmentController::class,
            'services' => \App\Http\Controllers\ServiceController::class,
        ]);
    });

    // Storage overview (Master & Super Admin)
    Route::group(['middleware' => ['role:master|Super Administrator']], function () {
        Route::get('/storage-overview', [\App\Http\Controllers\HomeController::class, 'storageOverview'])
            ->name('storage.overview');
    });

    // Master only
    Route::group(['middleware' => ['role:master']], function () {
        Route::resources([
            'roles' => \App\Http\Controllers\RoleController::class,
            'ocr-jobs' => OcrJobController::class,
            'ui-translations' => UiTranslationController::class,
        ]);
    });
    
    // Hierarchical structure operations
    Route::post('/physical-locations/add-room', [PhysicalLocationController::class, 'addRoom'])->name('physical-locations.add-room');
    Route::post('/physical-locations/add-row', [PhysicalLocationController::class, 'addRow'])->name('physical-locations.add-row');
    Route::post('/physical-locations/add-shelf', [PhysicalLocationController::class, 'addShelf'])->name('physical-locations.add-shelf');
    Route::post('/physical-locations/add-box', [PhysicalLocationController::class, 'addBox'])->name('physical-locations.add-box');
    
    // Box operations
    Route::put('/boxes/{box}', [PhysicalLocationController::class, 'updateBox'])->name('physical-locations.update-box');
    Route::delete('/boxes/{box}', [PhysicalLocationController::class, 'destroyBox'])->name('physical-locations.destroy-box');

    // Destruction Requests export
    Route::get('/documents-destructions/export', [DocumentDestructionRequestController::class, 'export'])->name('documents-destructions.export');
    // Deletion logs (permanently deleted documents)
    Route::get('/logs/deletions', [DocumentDestructionRequestController::class, 'deletionLogs'])->name('logs.deletions');
    Route::get('/logs/deletions/export', [DocumentDestructionRequestController::class, 'exportDeletionLogs'])->name('logs.deletions.export');

    // Permanently deleted documents history
    Route::get('/documents/destructions', [DocumentController::class, 'destructions'])->name('documents.destructions');

    // All documents (card view design)
    Route::get('/documents/all', [DocumentController::class, 'byCategory'])->name('documents.all');

    // Resource routes for models
    // Resource routes for models
    Route::resources([
        // 'users' => UserController::class, // Moved to role group
        // 'roles' => \App\Http\Controllers\RoleController::class, // Moved to role group
        'categories' => CategoryController::class,
        'subcategories' => \App\Http\Controllers\SubcategoryController::class,
        // 'departments' => \App\Http\Controllers\DepartmentController::class, // Moved to role group
        // 'sub-departments' => \App\Http\Controllers\SubDepartmentController::class, // Moved to role group
        // 'services' => \App\Http\Controllers\ServiceController::class, // Moved to role group
        'documents' => DocumentController::class,
        'documents-destructions' => DocumentDestructionRequestController::class,
        'document-movements' => DocumentMovementController::class,
        'document-versions' => DocumentVersionController::class,
        // 'ocr-jobs' => OcrJobController::class, // Moved to role group
        // 'physical-locations' => PhysicalLocationController::class, // Moved to role group
        'workflow-rules' => WorkFlowRuleController::class,
        'tags' => \App\Http\Controllers\TagController::class,
        // 'ui-translations' => UiTranslationController::class, // Moved to role group
        'folders' => \App\Http\Controllers\FolderController::class,
    ]);

    // Subcategories route
    Route::get('/categories/{category}/subcategories', [CategoryController::class, 'subcategories'])->name('categories.subcategories');

    Route::get('/workflow-rules/by-department/{departmentId}',[WorkFlowRuleController::class,'byDepartment'])->name('workflow-rules.by-department');
    Route::post('/workflow-rules/store/{departmentId}',[WorkFlowRuleController::class,'store'])->name('workflow-rules.store.department');


    Route::get('/documents/by-category/{categoryId?}',[DocumentController::class,'byCategory'])->name('documents.by-category');
    Route::get('/documents/by-subcategory/{subcategoryId}',[DocumentController::class,'bySubcategory'])->name('documents.by-subcategory');

    Route::get('/document-versions/{documentId}/create',[DocumentVersionController::class,'create'])->name('document-versions.document.create');

    Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
    Route::put('/users/{user}/image', [UserController::class, 'updateImage'])->name('users.updateImage');

    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::put('/documents/{id}/rename', [DocumentController::class, 'rename'])->name('documents.rename');
    Route::put('/documents/{id}/lock', [DocumentController::class, 'lock'])->name('documents.lock');
    Route::put('/documents/{id}/unlock', [DocumentController::class, 'unlock'])->name('documents.unlock');
    Route::put('/documents/{id}/approve', [DocumentController::class, 'approve'])->name('documents.approve');
    Route::put('/documents/{id}/decline', [DocumentController::class, 'decline'])->name('documents.decline');
    Route::delete('/documents/{id}/permanent-delete', [DocumentController::class, 'permanentDelete'])->name('documents.permanent-delete');
    Route::get('/documents/{id}/metadata', [DocumentController::class, 'getMetadata'])->name('documents.metadata');
    Route::put('/documents/{id}/metadata', [DocumentController::class, 'updateMetadata'])->name('documents.metadata.update');

    Route::put('/documents-destructions/{id}/approve', [DocumentDestructionRequestController::class, 'approve'])->name('documents-destructions.approve');
    Route::put('/documents-destructions/{id}/decline', [DocumentDestructionRequestController::class, 'decline'])->name('documents-destructions.decline');
    Route::put('/documents-destructions/{id}/postpone', [DocumentDestructionRequestController::class, 'postpone'])->name('documents-destructions.postpone');
    Route::put('/documents/{documentId}/postpone-expiration', [DocumentDestructionRequestController::class, 'postponeDocument'])->name('documents.postpone-expiration');

    Route::get('/document-versions/by-document/{id}',[DocumentVersionController::class,'byDocument'])->name('document-versions.by-document');

    Route::get('/document-versions/{id}/ocr',[DocumentVersionController::class,'viewOcr'])->name('document-versions.ocr');
    Route::put('/document-versions/{id}/ocr/update',[DocumentVersionController::class,'updateOcr'])->name('document-versions.update.ocr');

    Route::get('/document-versions/{id}/preview',[DocumentVersionController::class,'preview'])->name('document-versions.preview');
    Route::get('/document-versions/{id}/fullscreen',[DocumentVersionController::class,'viewFullscreen'])->name('document-versions.fullscreen');
    Route::get('/documents/versions/{id}/file', [DocumentVersionController::class, 'getFile'])->name('documents.versions.file');
    Route::get('/documents/versions/{id}/pdf', [DocumentVersionController::class, 'getPdf'])->name('documents.versions.pdf');

    // Folder approvals (cascading to contained documents)
    Route::put('/folders/{folder}/approve', [\App\Http\Controllers\FolderController::class, 'approve'])->name('folders.approve');
    Route::put('/folders/{folder}/decline', [\App\Http\Controllers\FolderController::class, 'decline'])->name('folders.decline');


});

require __DIR__.'/auth.php';
