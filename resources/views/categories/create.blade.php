@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row mt-5">
            <div class="col-md-8">
                <div class="">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h4 class="card-title mb-1">{{ ui_t('pages.categories_form.title') }}</h4>
                                <p class="text-muted small mb-0">{{ ui_t('pages.categories_form.subtitle') }}</p>
                            </div>
                          {{--  <button type="button" class="btn btn-dark btn-sm header-btn">
                                <i class="fas fa-plus me-1"></i> Category and subcategory
                            </button>--}}
                        </div>

                        <form id="categoryForm" method="post" action="{{ route('categories.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ ui_t('pages.categories_form.structure') }} <span class="text-danger">*</span></label>

                                @php
                                    $departments = \App\Models\Department::with('subDepartments.services')->get();
                                    $oldDept = old('department_id');
                                    $oldSubDept = old('sub_department_id');
                                    $oldService = old('service_id');
                                @endphp

                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select id="categoryDepartmentSelect" name="department_id" class="form-control custom-input" required>
                                            <option value="">{{ ui_t('pages.categories_form.select_structure') }}</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}" {{ $oldDept == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <select id="categorySubDepartmentSelect" name="sub_department_id" class="form-control custom-input" required>
                                            <option value="">{{ ui_t('pages.categories_form.select_department') }}</option>
                                            @foreach($departments as $department)
                                                @foreach($department->subDepartments as $sub)
                                                    <option value="{{ $sub->id }}" data-department-id="{{ $department->id }}" {{ $oldSubDept == $sub->id ? 'selected' : '' }}>
                                                        {{ $sub->name }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                        @error('sub_department_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <select id="categoryServiceSelect" name="service_id" class="form-control custom-input" required>
                                            <option value="">{{ ui_t('pages.categories_form.select_service') }}</option>
                                            @foreach($departments as $department)
                                                @foreach($department->subDepartments as $sub)
                                                    @foreach($sub->services as $service)
                                                        <option value="{{ $service->id }}" data-sub-department-id="{{ $sub->id }}" {{ $oldService == $service->id ? 'selected' : '' }}>
                                                            {{ $service->name }}
                                                        </option>
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                        </select>
                                        @error('service_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ ui_t('pages.categories_form.enter_category') }}</label>
                                <input type="text" name="category_name" value="{{ old('category_name') }}" class="form-control custom-input" id="categoryInput" placeholder="{{ ui_t('pages.categories_form.enter_category_ph') }}">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ ui_t('pages.categories_form.expiry_value') }}</label>
                                    <input type="number" min="1" name="expiry_value" value="{{ old('expiry_value') }}" class="form-control custom-input" placeholder="{{ ui_t('pages.categories_form.expiry_value_example') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ ui_t('pages.categories_form.expiry_unit') }}</label>
                                    <select name="expiry_unit" class="form-control custom-input" required>
                                        <option value="">{{ ui_t('pages.categories_form.select_unit') }}</option>
                                        <option value="days" {{ old('expiry_unit')=='days'?'selected':'' }}>{{ ui_t('pages.categories_form.days') }}</option>
                                        <option value="months" {{ old('expiry_unit')=='months'?'selected':'' }}>{{ ui_t('pages.categories_form.months') }}</option>
                                        <option value="years" {{ old('expiry_unit')=='years'?'selected':'' }}>{{ ui_t('pages.categories_form.years') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="subcategory-section">
                                <label class="form-label fw-medium mb-3">{{ ui_t('pages.categories_form.enter_subcategory') }} <span class="text-muted">{{ ui_t('pages.categories_form.optional') }}</span></label>
                                <div class="subcategory-visual-container">
                                    <div class="subcategory-container" id="subcategoryContainer">
                                        {{-- Subcategory inputs will be added dynamically by JS; no default value --}}
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-upload w-50 d-block mb-3" id="addSubcategoryBtn">{{ ui_t('pages.categories_form.add_new_subcategory') }}</button>
                            <button type="submit" class="btn-upload w-75 ">{{ ui_t('pages.categories_form.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Preview Section --}}
        <div class="row mt-5 w-50">
            <div class="col-md-8">
                <div class="">
                    <div class="card-body">
                        <h5 class="text-primary">{{ ui_t('pages.categories_form.live_preview') }}</h5>
                        <div class="mb-3">
                            <input type="text" class="form-control custom-input" id="liveCategory" placeholder="{{ ui_t('pages.categories_form.category_preview') }}" readonly>
                        </div>

                        <div class="subcategory-section">
                            <div class="subcategory-visual-container">
                                <div class="subcategory-container bg-ver" id="liveSubcategories">
                                    <!-- Subcategory previews will be appended here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Scripts --}}
    <script>
        // Cascading selects for Department -> Sub-Department -> Service
        (function() {
            const deptSelect = document.getElementById('categoryDepartmentSelect');
            const subDeptSelect = document.getElementById('categorySubDepartmentSelect');
            const serviceSelect = document.getElementById('categoryServiceSelect');

            function filterSubDepartments() {
                const deptId = deptSelect.value;
                Array.from(subDeptSelect.options).forEach(opt => {
                    if (!opt.value) return; // keep placeholder
                    const match = !deptId || opt.dataset.departmentId === deptId;
                    opt.hidden = !match;
                    if (!match && opt.selected) {
                        opt.selected = false;
                    }
                });
                filterServices();
            }

            function filterServices() {
                const subId = subDeptSelect.value;
                Array.from(serviceSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = !subId || opt.dataset.subDepartmentId === subId;
                    opt.hidden = !match;
                    if (!match && opt.selected) {
                        opt.selected = false;
                    }
                });
            }

            if (deptSelect && subDeptSelect && serviceSelect) {
                deptSelect.addEventListener('change', filterSubDepartments);
                subDeptSelect.addEventListener('change', filterServices);
                // initial filter based on old() values
                filterSubDepartments();
            }
        })();
        const translations = {
            enterSubcategory: @json(ui_t('pages.categories_form.enter_subcategory')),
            removeSubcategory: @json(ui_t('pages.categories_form.remove_subcategory')),
        };
        const categoryInput = document.getElementById('categoryInput');
        const liveCategory = document.getElementById('liveCategory');
        const subcategoryContainer = document.getElementById('subcategoryContainer');
        const liveSubcategories = document.getElementById('liveSubcategories');
        const addBtn = document.getElementById('addSubcategoryBtn');

        // Sync category input
        categoryInput.addEventListener('input', function () {
            liveCategory.value = this.value;
        });

        // Helper: create a subcategory input with remove button
        function createSubcategoryItem(value = '') {
            const newItem = document.createElement('div');
            newItem.classList.add('subcategory-item', 'mb-3', 'd-flex', 'align-items-center');

            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = 'subcategories[]';
            newInput.classList.add('form-control', 'custom-input', 'subcategory-input');
            newInput.placeholder = translations.enterSubcategory;
            newInput.value = value;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.classList.add('btn', 'btn-sm', 'btn-danger', 'ms-2', 'remove-subcategory');
            removeBtn.title = translations.removeSubcategory;
            removeBtn.innerHTML = '<i class="fas fa-trash text-white"></i>';

            removeBtn.addEventListener('click', () => {
                newItem.remove();
                syncSubcategories();
            });

            newItem.appendChild(newInput);
            newItem.appendChild(removeBtn);

            // Also sync live preview on input change
            newInput.addEventListener('input', syncSubcategories);

            return newItem;
        }

        // Initial setup: replace existing inputs with new styled ones (to add remove buttons)
        function initializeSubcategories() {
            const oldItems = Array.from(subcategoryContainer.querySelectorAll('.subcategory-item'));
            oldItems.forEach(oldItem => {
                const val = oldItem.querySelector('input').value;
                const newItem = createSubcategoryItem(val);
                oldItem.replaceWith(newItem);
            });
        }

        // Sync subcategories preview
        function syncSubcategories() {
            liveSubcategories.innerHTML = '';
            const inputs = subcategoryContainer.querySelectorAll('.subcategory-input');
            inputs.forEach((input) => {
                if (input.value.trim() !== '') { // Only show non-empty subcategories in preview
                    const clone = input.cloneNode();
                    clone.setAttribute('readonly', true);
                    clone.value = input.value;
                    clone.classList.add('mb-3');
                    liveSubcategories.appendChild(clone);
                }
            });
        }

        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const newItem = createSubcategoryItem();
            subcategoryContainer.appendChild(newItem);
            syncSubcategories();
        });

        // Form submission handler to clean up empty subcategories
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const subcategoryInputs = subcategoryContainer.querySelectorAll('.subcategory-input');
            subcategoryInputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.disabled = true; // Disable empty inputs so they won't be submitted
                }
            });
        });

        // Run on page load
        initializeSubcategories();
        syncSubcategories();
    </script>
@endsection
