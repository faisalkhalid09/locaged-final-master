<header class="header d-md-flex justify-content-between align-items-center">
    <div class="header-left">
        <livewire:document-elastic-search />
    </div>

    <div class="header-right mt-lg-0 mt-5 position-relative">
        <!-- <img src="{{ asset('assets/template/logo.png') }}" alt="logo" width="175" /> -->

        <div class="d-flex align-items-center">
            <a href="{{ route('documents.all', ['favoritesOnly' => true]) }}" class="me-3 align-middle" title="{{ ui_t('header.favorites') }}" aria-label="{{ ui_t('header.favorites') }}">
                <i class="fa-solid fa-star" style="color:#f59e0b"></i>
            </a>
            {{-- Help button removed as requested --}}
            @livewire('notification-dropdown')
        </div>

        <div class="user-avatar ps-2">

            <div class="d-flex justify-content-between align-items-center">
                
                <div id="toggleDropdown" class="d-flex pointer align-items-center">
                    @php
                        $authUserImageUrl = auth()->user()?->avatar_url ?? asset('assets/user.png');
                    @endphp
                    <img
                        src="{{ $authUserImageUrl }}"
                        class="user me-1"
                        alt="{{ ui_t('actions.user') }}"
                        onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                    />
                    <div class="user-info me-2">
                        <span class="name">{{ auth()->user()->full_name }}</span>
                        <span class="role">{{ auth()->user()->role }}</span>
                    </div>
                    <i class="fa-solid fa-caret-down pointer"></i>
                </div>

                <div class="postion-relative me-3">
                    <div class="pop p-4 d-none me-2" id="dropdownMenu">
                        <div class="d-flex align-items-center">
                            <img
                                src="{{ $authUserImageUrl }}"
                                class="user"
                                alt="{{ ui_t('actions.user') }}"
                                onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                            />
                            <div class="ms-3">
                                <h3>{{ auth()->user()->full_name }}</h3>
                                <h4>{{ auth()->user()->email }}</h4>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-3 mb-2 pointer">
                            <a href="{{ route('profile.show') }}" class="d-flex justify-content-between align-items-center w-100 text-decoration-none text-dark">
                                <h6 class="d-flex align-items-center mb-0">
                                    <i class="fa-solid fa-user me-2" style="color: #9E9E9E;"></i>{{ ui_t('header.my_profile') }}
                                </h6>
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>

                        <div class="d-flex justify-content-between mb-2 pointer" id="languageSelector">
                            <h6 class="d-flex align-items-center mb-0">
                                <i class="fa-solid fa-earth-americas me-2" style="color: #9E9E9E;"></i>{{ auth()->user()->locale === 'en' ? ui_t('header.english_us') : (auth()->user()->locale === 'ar' ? ui_t('header.arabic') : ui_t('header.french')) }}
                            </h6>
                            <i class="fa-solid fa-chevron-right pointer" id="toggleDropdown2"></i>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn p-0 border-0 bg-transparent w-100 text-start">
                                <div class="d-flex justify-content-between mb-2 align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fa-solid fa-arrow-right-from-bracket me-2" style="color: #9E9E9E;"></i>{{ ui_t('header.logout') }}
                                    </h6>
                                    <i class="fa-solid fa-chevron-right pointer"></i>
                                </div>
                            </button>
                        </form>
                    </div>

                    <div class="language p-4 d-none" id="dropdownMenu2">
                        <h3 class="mb-3"><i class="fa-solid fa-angle-left me-2 pointer" id="closeLanguage" style="color: #9E9E9E;"></i>{{ ui_t('header.change_language') }} </h3>
                        <div class="">
                            <form method="POST" action="{{ route('ui-translations.changeLocale') }}" id="languageForm">
                                @csrf
                                <div class="d-flex justify-content-between mb-2 pointer" onclick="document.getElementById('lang_fr').click();">
                                    <h6><img src="{{ asset('assets/Flags.svg') }}" class="me-2" alt="">{{ ui_t('header.french') }}</h6>
                                    <input type="radio" name="locale" value="fr" id="lang_fr" class="form-check-input ms-2" {{ auth()->user()->locale === 'fr' ? 'checked' : '' }} onchange="document.getElementById('languageForm').submit();">
                                </div>
                                <div class="d-flex justify-content-between mb-2 pointer" onclick="document.getElementById('lang_en').click();">
                                    <h6><img src="{{ asset('assets/Flags (1).svg') }}" class="me-2" alt="">{{ ui_t('header.english_us') }}</h6>
                                    <input type="radio" name="locale" value="en" id="lang_en" class="form-check-input ms-2" {{ auth()->user()->locale === 'en' ? 'checked' : '' }} onchange="document.getElementById('languageForm').submit();">
                                </div>
                                <div class="d-flex justify-content-between mb-2 pointer" onclick="document.getElementById('lang_ar').click();">
                                    <h6><img src="{{ asset('assets/Flags (2).svg') }}" class="me-2" alt="">{{ ui_t('header.arabic') }} </h6>
                                    <input type="radio" name="locale" value="ar" id="lang_ar" class="form-check-input ms-2" {{ auth()->user()->locale === 'ar' ? 'checked' : '' }} onchange="document.getElementById('languageForm').submit();">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <img src="{{ \App\Support\Branding::headerLogoUrl() }}" alt="Logo" width="100" />
            </div>
        </div>
    </div>
</header>
