@extends('layouts.app')

@section('content')

    <div class="activity-log px-4 px-md-0">
        <div class="d-md-flex mt-5 mb-4">
            <h4 class="mb-4">{{ ui_t('pages.activity_log.activity_log') }}</h4>
            <div class="btn-group2 mb-2 mx-auto">
                <button class="me-4  button-active2">
                    <a href="{{ route('users.logs') }}" class="text-decoration-none">{{ ui_t('pages.activity_log.activity_log') }}</a>
                </button>
                <button class="me-4">
                    <a href="{{ route('documents.index') }}" class="text-decoration-none">{{ ui_t('pages.file_audit') }}</a>
                </button>
                <button class="me-4">
                    <a href="{{ route('logs.deletions') }}" class="text-decoration-none">{{ __('Deletion log') }}</a>
                </button>
            </div>
        </div>

        <livewire:activity-logs-table/>

    </div>

@endsection
