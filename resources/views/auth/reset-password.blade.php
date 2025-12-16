@extends('layouts.guest')

@section('content')
    <div class="sign">
        <h2>{{ ui_t('auth.ui.sign_in_title') }}</h2>
        <p>{{ ui_t('auth.ui.sign_in_intro') }}</p>
        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div>
                <label for="email" class="mb-3 mt-3">{{ ui_t('auth.ui.email') }}<span>*</span></label>
                <input
                    type="email"
                    class="form-control mb-4"
                    placeholder="{{ ui_t('auth.ui.email_placeholder') }}"
                    name="email"
                    value="{{ old('email') }}"
                    required  autofocus
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <label for="password" class="mb-3 mt-3">{{ ui_t('auth.ui.password') }}<span>*</span></label>
                <input
                    type="password"
                    class="form-control mb-4"
                    name="password"
                    value="{{ old('password') }}"
                    required autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <label for="password_confirmation" class="mb-3 mt-3">{{ ui_t('auth.ui.confirm_password') }}<span>*</span></label>
                <input
                    type="password"
                    class="form-control mb-4"
                    name="password_confirmation"
                    value="{{ old('password_confirmation') }}"
                    required autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />
            <div class="flex items-center justify-end mt-4">
                <button class="sign-btn mt-4" type="submit">
                    {{ ui_t('auth.ui.reset_password') }}
                </button>
            </div>
        </form>

    </div>
@endsection
