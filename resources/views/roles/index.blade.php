@extends('layouts.app')

@section('content')

    <div class="">
        <div class=" mb-3">
            <h4 class="fw-bold">{{ ui_t('pages.roles.title') }}</h4>
            <div class="d-flex justify-content-between mt-4 pb-2 border-bottom mb-4 notification-info w-100">
                <div class=" d-flex gap-2  ">
                    @can('viewAny',\Spatie\Permission\Models\Role::class)
                        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-add">{{ ui_t('pages.roles.roles_btn') }}</a>
                    @endcan
                    @can('viewAny',\App\Models\User::class)
                            <a href="{{ route('users.index') }}" class="btn btn-sm btn-add">{{ ui_t('pages.roles.users_btn') }}</a>
                    @endcan



                </div>
                @can('create',\Spatie\Permission\Models\Role::class)
                    <div>
                        <a href="{{ route('roles.create') }}" class="btn btn-sm btn-dark" id="nextBtn">{{ ui_t('pages.roles.add_role') }}</a>
                    </div>
                @endcan

            </div>
        </div>

        <p class="text-muted">{{ ui_t('pages.roles.manage') }}</p>

        <div class="audience-table-container w-100">
            <table class="table table-hover align-middle audience-table">
                <thead>
                <tr>
                    <th>{{ ui_t('pages.roles.table.name') }}</th>
                    <th>{{ ui_t('pages.roles.table.permissions') }}</th>
                    <th>{{ ui_t('pages.roles.table.users') }}</th>
                    <th>{{ ui_t('pages.roles.table.created_at') }}</th>
                    <th>{{ ui_t('pages.roles.table.updated_at') }}</th>
                    <th>{{ ui_t('pages.roles.table.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td class="name py-4">{{ $role->name }}</td>
                        <td class="email">{{ $role->permissions_count ?? 0 }}</td>
                        <td class="email">{{ $role->users_count ?? 0 }}</td>
                        <td class="audience-permissions-text">{{ $role->created_at }}</td>
                        <td class="audience-permissions-text">{{ $role->updated_at }}</td>
                        <td class="audience-permissions-text">
                            @can('update',$role)
                                <a href="{{ route('roles.edit',['role' => $role->id]) }}" class="btn btn-action">{{ ui_t('pages.roles.edit') }}</a>

                            @endcan
                            @can('delete',$role)
                                    <button type="button"
                                            data-id="{{ $role->id }}"
                                            data-name="{{ $role->name }}"
                                            data-url="{{ route('roles.destroy', $role->id) }}"
                                            class="btn btn-action trigger-action"
                                            data-method="DELETE"
                                            data-button-text="{{ ui_t('pages.roles.confirm') }}"
                                            data-title="{{ ui_t('pages.roles.delete_title', ['name' => $role->name]) }}"
                                            data-body="{{ ui_t('pages.roles.delete_body') }}">
                                        {{ ui_t('pages.roles.delete') }}
                                    </button>
                            @endcan


                        </td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    </div>

    @include('components.modals.confirm-modal')

@endsection
