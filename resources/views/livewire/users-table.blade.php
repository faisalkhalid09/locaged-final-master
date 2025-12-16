<div class="mt-5">

    <div class="recent-files-section">
        <div class="table-controls">
            <div class="search-files">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="{{ ui_t('pages.users_page.search_by_name') }}" wire:model.live="search" />
            </div>
            <div class="table-filters">
                <select class="form-select" wire:model.change="role">
                    <option value="">{{ ui_t('pages.users_page.filters.role') }}</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>

                <select class="form-select" wire:model.change="department">
                    <option value="">{{ ui_t('pages.users_page.filters.department') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                <div class="d-flex align-items-center gap-1">
                    <label>{{ ui_t('pages.users_page.filters.from') }}: </label>
                    <input type="date" class="form-control" wire:model.change="dateFrom" placeholder="{{ ui_t('pages.users_page.filters.from') }}" />
                </div>

                <div class="d-flex align-items-center gap-1">
                    <label>{{ ui_t('pages.users_page.filters.to') }}: </label>
                    <input type="date" class="form-control" wire:model.change="dateTo" placeholder="{{ ui_t('pages.users_page.filters.to') }}" />
                </div>

                <!-- Reset button -->
                <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-danger text-nowrap">
                    {{ ui_t('pages.users_page.filters.reset_filters') }}
                </button>

            </div>
        </div>

        <table class="files-table">
            <thead>
            <tr>
                <th>
                    <input type="checkbox" wire:model.change="selectAll">
                </th>
                <th>{{ ui_t('pages.users_page.table.user') }}</th>
                <th>{{ ui_t('pages.users_page.table.email') }}</th>
                <th>{{ ui_t('pages.users_page.table.department') }}</th>
                <th>{{ ui_t('pages.users_page.table.role') }}</th>
                <th>{{ ui_t('pages.users_page.table.joined') }}</th>
                <th class="d-flex justify-content-center">{{ ui_t('pages.users_page.table.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr class="user-row" data-user-id="{{ $user->id }}">
                    <td>
                        <input type="checkbox"
                               wire:model.change="checkedUsers"
                               value="{{ $user->id }}">
                    </td>
                    <td>
                        <div class="img-history d-flex align-items-center">
                            <div class="me-2">
                                <img
                                    src="{{ $user->avatar_url }}"
                                    alt="user"
                                    onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                                />
                            </div>
                            <div>{{ $user->full_name }}</div>
                        </div>
                    </td>
                    <td>
                        <div>{{ $user->email}}</div>
                    </td>
                    <td>
                        <div>
                            @if($user->departments->count() > 0)
                                {{ $user->departments->pluck('name')->join(', ') }}
                            @else
                                -
                            @endif
                        </div>
                    </td>
                    <td>
                        <div>{{ $user->role }}</div>
                    </td>


                    <td>
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>
                    <td>
                        <div class="file-actions d-flex justify-content-center">
                            @can('view',$user)
                                <a href="{{ route('users.show',['user' => $user->id]) }}" class="btn-action">{{ ui_t('pages.users_page.profile') }}</a>
                                <a href="{{ route('user.activity',['id' => $user->id]) }}" class="btn-action">{{ ui_t('pages.users_page.activity') }}</a>
                            @endcan
                            @can('update',$user)
                                    <button
                                        class="btn btn-sm btn-action edit-user-btn"
                                        data-id="{{ $user->id }}"
                                        data-fullname="{{ $user->full_name }}"
                                        data-email="{{ $user->email }}"
                                        data-department-ids="{{ $user->departments->pluck('id')->join(',') }}"
                                        data-sub-department-id="{{ $user->subDepartments->pluck('id')->first() }}"
                                        data-service-ids="{{ $user->services->pluck('id')->join(',') }}"
                                        data-role-id="{{ $user->roles->first()?->id }}"
                                    >
                                        {{ ui_t('pages.users_page.edit') }}
                                    </button>
                                @endcan
                                @can('delete',$user)
                            <button type="button"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-url="{{ route('users.destroy', $user->id) }}"
                                    class="btn btn-sm btn-action  trigger-action"
                                    data-method="DELETE"
                                    data-button-text="{{ ui_t('pages.users_page.confirm') }}"
                                    data-title="{{ ui_t('pages.users_page.delete_user_title', ['name' => $user->full_name]) }}"
                                    data-body="{{ ui_t('pages.documents.cannot_undo') }}">
                                {{ ui_t('pages.users_page.delete') }}
                            </button>
                                    @endcan
                        </div>
                    </td>
                </tr>
            @endforeach


            </tbody>
        </table>


        @include('components.modals.confirm-modal')
        @include('components.modals.user-modal')
        <x-pagination :items="$users"></x-pagination>
    </div>

</div>


