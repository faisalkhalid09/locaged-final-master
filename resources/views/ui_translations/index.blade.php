@extends('layouts.app')


@section('content')
    <div class="localization ">
        <div class="d-flex justify-content-between align-items-start w-75 mt-5">
            <div class="w-100">
                {{-- Language Selector --}}
                <div class="mb-4">
                    <label for="" class="mb-2">{{ ui_t('pages.translations.language') }}</label>
                    <form method="POST" action="{{ route('ui-translations.changeLocale') }}">
                        @csrf
                        <select class="form-control w-50" name="locale" onchange="this.form.submit()">
                            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                            <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>Français</option>
                            <option value="ar" {{ app()->getLocale() === 'ar' ? 'selected' : '' }}>العربية</option>
                        </select>
                    </form>
                </div>
                
                {{-- Timezone Selector --}}
                <div class="mb-4">
                    <label for="timezone" class="mb-2">{{ ui_t('pages.translations.timezone') }}</label>
                    <form method="POST" action="{{ route('ui-translations.branding') }}" id="timezone-form">
                        @csrf
                        <select class="form-control w-50" name="timezone" id="timezone" onchange="this.form.submit()">
                            <option value="">{{ ui_t('pages.translations.select_timezone') }}</option>
                            <optgroup label="Europe">
                                <option value="Europe/Paris" {{ \App\Support\Branding::getTimezone() === 'Europe/Paris' ? 'selected' : '' }}>CET (Europe/Paris)</option>
                                <option value="Europe/London" {{ \App\Support\Branding::getTimezone() === 'Europe/London' ? 'selected' : '' }}>WET (Europe/London)</option>
                                <option value="Europe/Athens" {{ \App\Support\Branding::getTimezone() === 'Europe/Athens' ? 'selected' : '' }}>EET (Europe/Athens)</option>
                                <option value="Europe/Berlin" {{ \App\Support\Branding::getTimezone() === 'Europe/Berlin' ? 'selected' : '' }}>Europe/Berlin</option>
                            </optgroup>
                            <optgroup label="Middle East">
                                <option value="Asia/Riyadh" {{ \App\Support\Branding::getTimezone() === 'Asia/Riyadh' ? 'selected' : '' }}>Arabia (Asia/Riyadh)</option>
                                <option value="Asia/Dubai" {{ \App\Support\Branding::getTimezone() === 'Asia/Dubai' ? 'selected' : '' }}>Gulf (Asia/Dubai)</option>
                                <option value="Asia/Baghdad" {{ \App\Support\Branding::getTimezone() === 'Asia/Baghdad' ? 'selected' : '' }}>Arabia (Asia/Baghdad)</option>
                                <option value="Asia/Kuwait" {{ \App\Support\Branding::getTimezone() === 'Asia/Kuwait' ? 'selected' : '' }}>Arabia (Asia/Kuwait)</option>
                            </optgroup>
                            <optgroup label="Americas">
                                <option value="America/New_York" {{ \App\Support\Branding::getTimezone() === 'America/New_York' ? 'selected' : '' }}>EST (America/New_York)</option>
                                <option value="America/Chicago" {{ \App\Support\Branding::getTimezone() === 'America/Chicago' ? 'selected' : '' }}>CST (America/Chicago)</option>
                                <option value="America/Denver" {{ \App\Support\Branding::getTimezone() === 'America/Denver' ? 'selected' : '' }}>MST (America/Denver)</option>
                                <option value="America/Los_Angeles" {{ \App\Support\Branding::getTimezone() === 'America/Los_Angeles' ? 'selected' : '' }}>PST (America/Los_Angeles)</option>
                            </optgroup>
                            <optgroup label="Asia Pacific">
                                <option value="Asia/Tokyo" {{ \App\Support\Branding::getTimezone() === 'Asia/Tokyo' ? 'selected' : '' }}>JST (Asia/Tokyo)</option>
                                <option value="Asia/Singapore" {{ \App\Support\Branding::getTimezone() === 'Asia/Singapore' ? 'selected' : '' }}>SGT (Asia/Singapore)</option>
                                <option value="Asia/Hong_Kong" {{ \App\Support\Branding::getTimezone() === 'Asia/Hong_Kong' ? 'selected' : '' }}>HKT (Asia/Hong_Kong)</option>
                            </optgroup>
                            <optgroup label="Africa">
                                <option value="Africa/Casablanca" {{ \App\Support\Branding::getTimezone() === 'Africa/Casablanca' ? 'selected' : '' }}>WET (Africa/Casablanca - Morocco UTC+1)</option>
                            </optgroup>
                            <optgroup label="Other">
                                <option value="UTC" {{ \App\Support\Branding::getTimezone() === 'UTC' ? 'selected' : '' }}>UTC</option>
                            </optgroup>
                        </select>
                        <small class="text-muted d-block mt-1">{{ ui_t('pages.translations.current') }} {{ \App\Support\Branding::getTimezone() }}</small>
                    </form>
                </div>
            </div>

            @can('create',\App\Models\UiTranslation::class)
                <div class="text-nowrap">
                    <a class="btn btn-dark" href="{{ route('ui-translations.create') }}">{{ ui_t('pages.translations.add_button') }}</a>
                </div>
            @endcan
        </div>



     {{--   <label for="" class="mb-3">Region</label>
        <select class="form-control w-25">
            <option value="">Select Region</option>
            <option value="">Select Region</option>
            <option value="">Select Region</option>
            <option value="">Select Region</option>
        </select>--}}
        <div class="mt-5 mb-2 w-75">
            <form method="post" action="{{ route('toggle.rtl') }}">
                @csrf
                <h2 class="mb-2">{{ ui_t('pages.translations.rtl_title') }}</h2>
                <div class="layout d-flex justify-content-between align-items-center">
                    <h4>{{ ui_t('pages.translations.enable_rtl') }}</h4>
                    <label class="switch">
                        <input type="checkbox" name="rtl" value="1" onchange="this.form.submit()" {{ session('rtl') ? 'checked' : '' }} />
                        <span class="slider"></span>
                    </label>
                </div>
            </form>


            <h2 class="mt-3 mb-3">{{ ui_t('actions.preview') }}</h2>
            <p>{{ ui_t('pages.translations.introduction') ?? ui_t('pages.translations.title') }}</p>
            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-outline-secondary">{{ ui_t('actions.cancel') }}</button>
                <button class="btn-upload">{{ ui_t('actions.save') }}</button>
            </div>
        </div>

        <div class="mt-5 mb-5 w-75">
            <h2 class="mb-3">{{ ui_t('pages.translations.branding') }}</h2>
            <form method="post" action="{{ route('ui-translations.branding') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ ui_t('pages.translations.header_logo') }}</label>
                    <input type="file" name="header_logo" class="form-control" accept="image/*" />
                    <small class="text-muted">{{ ui_t('pages.translations.image_limit_5mb') }}</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui_t('pages.translations.login_left_image') }}</label>
                    <input type="file" name="login_left_image" class="form-control" accept="image/*" />
                    <small class="text-muted">{{ ui_t('pages.translations.image_limit_8mb') }}</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui_t('pages.translations.max_users') }}</label>
                    <input type="number" name="max_users" class="form-control" 
                           value="{{ \App\Support\Branding::getMaxUsers() }}" 
                           min="0" placeholder="{{ ui_t('pages.translations.zero_unlimited_ph') }}" />
                    <small class="text-muted">{{ ui_t('pages.translations.max_users_help') }}</small>
                    
                    @php
                        $currentUserCount = \App\Models\User::count();
                        $maxUsers = \App\Support\Branding::getMaxUsers();
                    @endphp
                    
                    <div class="mt-2">
                        @if($maxUsers > 0)
                            <div class="alert alert-sm alert-info mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>{{ ui_t('pages.translations.current') }}</strong> {{ $currentUserCount }} / {{ $maxUsers }} {{ ui_t('pages.users_page.users') }}
                                @if($currentUserCount >= $maxUsers)
                                    <span class="text-danger ms-2">
                                        <i class="fas fa-exclamation-triangle"></i> {{ ui_t('pages.translations.limit_reached') }}
                                    </span>
                                @else
                                    <span class="text-success ms-2">
                                        <i class="fas fa-check-circle"></i> {{ $maxUsers - $currentUserCount }} {{ ui_t('pages.translations.slots_remaining') }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-sm alert-success mb-0">
                                <i class="fas fa-users me-1"></i>
                                <strong>{{ ui_t('pages.translations.current') }}</strong> {{ $currentUserCount }} {{ ui_t('pages.users_page.users') }} ({{ ui_t('pages.users_page.unlimited') }})
                            </div>
                        @endif
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-dark">{{ ui_t('actions.update') }}</button>
                </div>
            </form>
        </div>
    </div>



@endsection
