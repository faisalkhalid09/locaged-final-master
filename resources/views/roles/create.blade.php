@extends('layouts.app')

@section('content')
    <div class="ml-4 mt-16 w-9/12">
        <form action="{{route('roles.store')}}" method="POST">
            @csrf

            <h4 class="fw-bold mb-3">{{ ui_t('pages.roles.create_title') }}</h4>



            <div class="mb-6">
                <label for="text" class="form-label">{{ ui_t('pages.roles.role_name') }}</label>
                <input type="text" value="{{old('name')}}" name="name" id="email" class="form-control w-25 mb-4 " placeholder="{{ ui_t('pages.roles.placeholder_examples') }}" >

                @foreach ($errors->get('name') as $error)
                    <p class="text-red-600">{{$error}}</p>
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 w-75">
                <button class="btn btn-dark" type="submit">{{ ui_t('pages.roles.create') }}</button>

                <div class="form-check">
                    <input type="checkbox" id="select-all" class="form-check-input" style="transform: scale(1.2);">
                    <label for="select-all" class="form-check-label ms-2">{{ ui_t('pages.roles.select_all') }}</label>
                </div>
            </div>


            <table class="table table-bordered align-middle">
                <thead>
                <tr>
                    <th>{{ ui_t('pages.roles.models.model') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.view_any') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.view_structure') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.view_own') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.create') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.edit') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.delete') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.restore') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.force_delete') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.approve') }}</th>
                    <th class="text-center">{{ ui_t('pages.roles.models.decline') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($permissions as $model => $actions)
                    <tr>
                        <td><strong>{{ ucfirst($model) }}</strong></td>
                        @foreach(['view any','view department' , 'view own', 'create', 'edit', 'delete','restore' , 'forceDelete' , 'approve' ,'decline'] as $action)
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
                                        {{ in_array($perm, old('permissions', [])) ? 'checked' : '' }}

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
