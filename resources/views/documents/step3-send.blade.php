@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-center align-items-center text-center mt-5 ">
        <div class="send-info">
            <img src="{{ asset('assets/Frame 2078547817.svg') }}" alt="check-mark">
            <h3 class="mb-3">{{ ui_t('pages.upload.file_sent_successfully') }}</h3>
            <p>{{ ui_t('pages.upload.review_status_message') }}</p>
        </div>
    </div>
@endsection
