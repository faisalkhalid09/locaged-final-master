@extends('layouts.app')

@section('content')
    <div class="">
        <h4 class="fw-bold mb-3">{{ ui_t('pages.roles.manage_permissions_for', ['name' => $role->name]) }}</h4>

        <form method="POST" action="{{ route('roles.update', $role->id) }}">
            @csrf
            @method('PUT')
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-dark" type="submit">{{ ui_t('pages.roles.save_permissions') }}</button>

                <div class="form-check">
                    <input type="checkbox" id="select-all" class="form-check-input" style="transform: scale(1.2);">
                    <label for="select-all" class="form-check-label ms-2">{{ ui_t('pages.roles.select_all') }}</label>
                </div>
            </div>

            <div class="mb-3" style="max-width: 320px;">
                <label for="role-name" class="form-label">Role Name</label>
                <input type="text"
                       id="role-name"
                       name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $role->name) }}">
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <table class="table table-bordered align-middle">
                <thead>
                <tr>
                    <th>{{ ui_t('pages.roles.models.model') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.view_any') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.view_structure') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.view_own') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.create') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.update') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.delete') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.restore') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.force_delete') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.approve') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.decline') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groupedPermissions as $model => $actions)
                    <tr>
                        <td><strong>{{ ucfirst($model) }}</strong></td>
                        @foreach(['view any','view department' , 'view own', 'create', 'update', 'delete','restore' , 'forceDelete' , 'approve' ,'decline'] as $action)
                            @php
                                $perm = $actions[$action]->name ?? null;
                            @endphp
                            <td class="text-center">
                                @if($perm)
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $perm }}"
                                        class="form-check-input "
                                        style="transform: scale(1.2);border: 1px solid #333"
                                        {{ in_array($perm, $rolePermissions) ? 'checked' : '' }}
                                    >

                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </form>
    </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const selectAll = document.getElementById('select-all');
                const checkboxes = document.querySelectorAll('input[name="permissions[]"]');

                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = selectAll.checked;
                    });
                });
            });
        </script>


@endsection
