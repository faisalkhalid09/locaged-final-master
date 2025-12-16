@extends('layouts.guest')

@section('content')

            <style>
                .login-card { width: min(70%, 520px); margin: 0 auto; }
            </style>
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/Logo 3.svg') }}" alt="Logo" style="max-width: 220px; width: 100%;" />
                </div>

                <h4 class="mb-2 text-center">{{ ui_t('auth.ui.forgot_password_q') }}</h4>
                <p class="text-muted small text-center mb-4">{{ ui_t('auth.ui.enter_email_send_link') }}</p>

                @if (session('status'))
                    <div class="alert alert-success border-success-subtle bg-success-subtle text-success shadow-sm mb-4" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert d-flex align-items-start bg-danger-subtle border border-danger-subtle shadow-sm mb-4 fade show" role="alert" aria-live="assertive">
                        <span class="me-3 mt-1 text-danger" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 14a1 1 0 1 1-1-1 1 1 0 0 1 1 1Zm0-4a1 1 0 0 1-2 0V7a1 1 0 0 1 2 0Z"/>
                            </svg>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-danger mb-1">{{ ui_t('auth.ui.we_couldnt_process') }}</div>
                            <ul class="mb-0 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li class="mb-1">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="{{ ui_t('actions.close') }}"></button>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const firstInvalid = document.querySelector('.is-invalid');
                            if (firstInvalid) { try { firstInvalid.focus(); } catch (e) {} }
                        });
                    </script>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="mt-2">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">{{ ui_t('auth.ui.email') }}</label>
                        <input
                            id="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="{{ ui_t('auth.ui.email_placeholder') }}"
                            name="email"
                            value="{{ old('email') }}"
                            required autofocus autocomplete="email"
                        />
                    </div>

                    <button class="btn btn-primary w-100" type="submit">{{ ui_t('auth.ui.email_reset_link') }}</button>
                </form>
            </div>
@endsection
