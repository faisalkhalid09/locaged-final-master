@extends('layouts.app')

@section('content')

    <div class="container my-5 position-relative">
        <h2 class="fw-bold">{{ ui_t('pages.workflow.title') }}</h2>
        <p class="new-mange">{{ ui_t('pages.workflow.manage_for', ['name' => $department->name]) }}</p>

        <h5 class="fw-bold my-4">{{ ui_t('pages.workflow.add_rule') }}</h5>
        <form method="post" action="{{ route('workflow-rules.store.department',['departmentId' => $department->id]) }}">
            @csrf
            <input type="hidden" name="department_id" value="{{ $department->id }}">
            <div class="row g-3 align-items-center add-role">
                <div class="col-md-4">
                    <label>{{ ui_t('pages.workflow.from_status') }}</label>
                    <select name="from_status" class="form-control" required>
                        <option value="" disabled selected>{{ ui_t('pages.workflow.from_status') }}</option>
                        @foreach (\App\Enums\DocumentStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('from_status') == $status->value ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>{{ ui_t('pages.workflow.to_status') }}</label>
                    <select name="to_status" class="form-control" required>
                        <option value="" disabled selected>{{ ui_t('pages.workflow.to_status') }}</option>
                        @foreach (\App\Enums\DocumentStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('to_status') == $status->value ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-upload mt-4" type="submit">{{ ui_t('pages.workflow.add') }}</button>
                </div>
            </div>
        </form>

        <h6 class="fw-bold mt-5">{{ ui_t('pages.workflow.existing') }}</h6>
        <div id="categoryList" class="mt-3 w-75">
            @foreach($rules as $rule)
                <div class="d-flex justify-content-between mb-3 align-items-center">
                    <span>From: {{ $rule->from_status }}</span>
                    <span>To: {{ $rule->to_status }}</span>
                    <span class="d-flex gap-2">
                        <!-- Edit button triggers modal -->
                        @can('update',$rule)
                            <button type="button" class="btn btn-action" data-bs-toggle="modal" data-bs-target="#editModal{{ $rule->id }}">
                            {{ ui_t('pages.workflow.edit') }}
                        </button>
                            <!-- Modal -->
                            @include('components.modals.edit-workflow-modal',['rule' => $rule])
                        @endcan


                        <!-- Delete form -->
                        @can('delete',$rule)
                            <form method="post" action="{{ route('workflow-rules.destroy', $rule->id) }}">
                            @csrf
                                @method('DELETE')
                            <button type="submit" class="btn btn-action">{{ ui_t('pages.workflow.delete') }}</button>
                        </form>
                        @endcan

                    </span>
                </div>


            @endforeach
        </div>

        <x-pagination :items="$rules"/>
    </div>


@endsection
