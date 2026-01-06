@extends('layouts.app')

@section('content')
    <div class="container my-5 position-relative">
        <h2 class="fw-bold">{{ ui_t('pages.structures_page.title') }}</h2>
        <p class="new-mange">{{ ui_t('pages.structures_page.manage') }}</p>

        @if($canCreateStructures)
            {{-- Departments management --}}
            <h5 class="fw-bold my-4">{{ ui_t('pages.structures_page.add') }}</h5>
            <form method="post" action="{{ route('departments.store') }}">
                @csrf
                <div class="row g-3 align-items-center add-role">
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.name') }}</label>
                        <input type="text" name="name" class="form-control py-3 mt-1" placeholder="{{ ui_t('pages.structures_page.placeholder_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.description') }}</label>
                        <textarea name="description" class="form-control py-3 mt-1" placeholder="{{ ui_t('pages.structures_page.placeholder_description') }}"></textarea>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-upload mt-4" type="submit">{{ ui_t('pages.structures_page.add_button') }}</button>
                    </div>
                </div>
            </form>

            {{-- Sub-Departments management --}}
            <h5 class="fw-bold my-4">{{ ui_t('pages.structures_page.sub_departments_title') }}</h5>
            <form method="post" action="{{ route('sub-departments.store') }}">
                @csrf
                <div class="row g-3 align-items-center add-role">
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.sub_departments_department_label') }}</label>
                        <select name="department_id" class="form-control py-3 mt-1">
                            @foreach($allDepartments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.sub_departments_name_label') }}</label>
                        <input type="text" name="name" class="form-control py-3 mt-1" placeholder="{{ ui_t('pages.structures_page.sub_departments_name_placeholder') }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-upload mt-4" type="submit">{{ ui_t('pages.structures_page.sub_departments_add_button') }}</button>
                    </div>
                </div>
            </form>

            {{-- Services management --}}
            <h5 class="fw-bold my-4">{{ ui_t('pages.structures_page.services_title') }}</h5>
            <form method="post" action="{{ route('services.store') }}">
                @csrf
                <div class="row g-3 align-items-center add-role">
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.services_sub_department_label') }}</label>
                        <select name="sub_department_id" class="form-control py-3 mt-1">
                            @foreach($allDepartments as $dept)
                                @foreach($dept->subDepartments as $sub)
                                    <option value="{{ $sub->id }}">{{ $dept->name }} - {{ $sub->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>{{ ui_t('pages.structures_page.services_name_label') }}</label>
                        <input type="text" name="name" class="form-control py-3 mt-1" placeholder="{{ ui_t('pages.structures_page.services_name_placeholder') }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-upload mt-4" type="submit">{{ ui_t('pages.structures_page.services_add_button') }}</button>
                    </div>
                </div>
            </form>
        @endif

        <h6 class="fw-bold mt-5">{{ ui_t('pages.structures_page.existing') }}</h6>

        {{-- Modern org-chart style layout for structures --}}
        <div id="categoryList" class="mt-3">
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                @foreach($departments as $department)
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0 org-card">
                            <div class="card-header d-flex justify-content-between align-items-start bg-white border-0 pb-0">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark rounded-pill"><i class="fas fa-building"></i></span>
                                        <h5 class="mb-0">{{ $department->name }}</h5>
                                    </div>
                                    @if($department->description)
                                        <p class="text-muted small mb-0 mt-1">{{ $department->description }}</p>
                                    @endif
                                </div>

                                <div class="d-flex gap-1">
                                    @can('update', $department)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $department->id }}" title="{{ ui_t('pages.structures_page.edit') }}">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        @include('components.modals.edit-department-modal', ['department' => $department])
                                    @endcan
                                    @can('delete',$department)
                                        <form method="post" action="{{ route('departments.destroy', $department->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ ui_t('pages.structures_page.delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>

                            <div class="card-body pt-3">
                                @if($department->subDepartments->count())
                                    <div class="org-branches">
                                        @foreach($department->subDepartments as $sub)
                                            <div class="org-node mb-3 p-3 rounded-3 border bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-secondary rounded-pill"><i class="fas fa-sitemap"></i></span>
                                                        <strong>{{ $sub->name }}</strong>
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        @can('update', $department)
                                                            <form method="post" action="{{ route('sub-departments.update', $sub->id) }}" class="d-inline-flex align-items-center">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="text" name="name" value="{{ $sub->name }}" class="form-control form-control-sm me-1" style="max-width: 180px;">
                                                                <button class="btn btn-sm btn-outline-primary" type="submit">
                                                                    <i class="fas fa-save"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                        @can('delete', $department)
                                                            <form method="post" action="{{ route('sub-departments.destroy', $sub->id) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </div>

                                                @if($sub->services->count())
                                                    <div class="d-flex flex-wrap gap-2 ms-1 org-services">
                                                        @foreach($sub->services as $service)
                                                            <div class="badge bg-white text-dark border d-flex align-items-center gap-2 py-2 px-3 shadow-sm rounded-pill">
                                                                <i class="fas fa-circle text-success small"></i>
                                                                <span class="small">{{ $service->name }}</span>
                                                                <div class="d-flex gap-1 ms-1">
                                                                    @can('update', $department)
                                                                        <form method="post" action="{{ route('services.update', $service->id) }}" class="d-inline-flex align-items-center">
                                                                            @csrf
                                                                            @method('PUT')
                                                                            <input type="text" name="name" value="{{ $service->name }}" class="form-control form-control-sm me-1 d-none service-edit-input" style="max-width: 160px;">
                                                                            <button class="btn btn-xs btn-link text-muted p-0 service-edit-toggle" type="button" title="{{ ui_t('pages.structures_page.edit') }}">
                                                                                <i class="fas fa-pen small"></i>
                                                                            </button>
                                                                        </form>
                                                                    @endcan
                                                                    @can('delete', $department)
                                                                        <form method="post" action="{{ route('services.destroy', $service->id) }}" class="d-inline">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn btn-xs btn-link text-danger p-0" title="{{ ui_t('pages.structures_page.delete') }}">
                                                                                <i class="fas fa-trash small"></i>
                                                                            </button>
                                                                        </form>
                                                                    @endcan
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-muted small mb-0 ms-1">{{ ui_t('pages.physical.actions.no_locations') }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">{{ ui_t('pages.physical.actions.no_rows') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <x-pagination :items="$departments"/>
    </div>

    <script>
        // Service name edit toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.service-edit-toggle').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const input = form.querySelector('.service-edit-input');
                    const nameSpan = form.closest('.badge').querySelector('span.small');
                    
                    if (input.classList.contains('d-none')) {
                        // Show input, hide original name text
                        input.classList.remove('d-none');
                        input.focus();
                        input.select();
                        nameSpan.classList.add('d-none');
                        this.innerHTML = '<i class="fas fa-save small"></i>';
                        this.classList.add('text-success');
                        this.classList.remove('text-muted');
                    } else {
                        // Submit form
                        form.submit();
                    }
                });
            });
            
            // Allow pressing Enter to submit
            document.querySelectorAll('.service-edit-input').forEach(function(input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.closest('form').submit();
                    }
                });
                
                // Cancel on Escape
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        const form = this.closest('form');
                        const nameSpan = form.closest('.badge').querySelector('span.small');
                        const btn = form.querySelector('.service-edit-toggle');
                        
                        this.classList.add('d-none');
                        nameSpan.classList.remove('d-none');
                        btn.innerHTML = '<i class="fas fa-pen small"></i>';
                        btn.classList.remove('text-success');
                        btn.classList.add('text-muted');
                    }
                });
            });
        });
    </script>
@endsection
