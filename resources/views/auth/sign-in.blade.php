@extends('layouts.guest')

@section('content')
            <style>
                body { font-family: Helvetica, Arial, sans-serif !important; }
                .login-card { width: min(70%, 520px); margin: 0 auto; }
            </style>
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/Logo 3.svg') }}" alt="Logo" style="max-width: 220px; width: 100%;" />
                </div>
                <div class="text-center mb-3">
                    <h5 class="mb-1">{{ ui_t('auth.ui.secure_space') }}</h5>
                </div>

                @if ($errors->any())
                    <div class="alert d-flex align-items-start bg-danger-subtle border border-danger-subtle shadow-sm mb-4 fade show" role="alert" aria-live="assertive">
                        <span class="me-3 mt-1 text-danger" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 14a1 1 0 1 1-1-1 1 1 0 0 1 1 1Zm0-4a1 1 0 0 1-2 0V7a1 1 0 0 1 2 0Z"/>
                            </svg>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-danger mb-1">{{ ui_t('auth.ui.sign_in_failed') }}</div>
                            <p class="mb-2 small text-muted">{{ ui_t('auth.ui.error_hint') }} <a href="{{ route('password.request') }}" class="text-decoration-none">{{ ui_t('auth.ui.reset_password_link') }}</a>.</p>
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

                <form method="post" action="{{ route('login') }}" class="mt-3">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">{{ ui_t('auth.ui.login') }}</label>
                        <input
                            id="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                        />
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label">{{ ui_t('auth.ui.password') }}</label>
                        <input
                            id="password"
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password"
                            required
                            autocomplete="current-password"
                        />
                    </div>

                    {{-- <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('password.request') }}" class="small text-decoration-none">{{ ui_t('auth.ui.forgot_password') }}</a>
                    </div> --}}

                    <button class="btn btn-primary w-100" type="submit">{{ ui_t('auth.ui.sign_in') }}</button>
                </form>
            </div>
@endsection()

