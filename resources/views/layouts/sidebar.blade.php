<nav id="sidebar" class="sidebar">
    <div class="text-center mb-2 d-flex justify-content-center ">
        <a href="{{ route('home') }}" class="text-decoration-none ms-0 ps-0"><img src="{{ asset('assets/template/Logo 1.svg') }}" alt="logo" class="sidebar-logo mb-3 mt-4 expanded-only pointer" /></a>
        <a href="{{ route('home') }}" class="text-decoration-none ms-1 ps-0"><img src="{{ asset('assets/template/Frame 2078547825 1.svg') }}" alt="" class="collapsed-only  mb-1 mt-4 pointer" /></a>
    </div>

    <ul class="sidebar-menu mt-1">
        @php
            $u = auth()->user();
        @endphp

        <li class="mt-1">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <img src="{{ asset('assets/template/dashboard-square-01.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.dashboard') }}</span>
            </a>
        </li>

        @can('create', \App\Models\Document::class)
        <li class="mt-1">
            <a href="{{ route('documents.create') }}" class="{{ request()->routeIs('documents.create') ? 'active' : '' }}">
                <img src="{{ asset('assets/template/icons8_upload-2.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.upload') }}</span>
            </a>
        </li>
        @endcan

        @php
            $u = auth()->user();
            $canDocumentsMenu = $u && (
                $u->can('viewAny', \App\Models\Document::class)
                || $u->can('viewAny', \App\Models\Category::class)
                || $u->can('viewAny', \App\Models\DocumentVersion::class)
                || $u->can('viewAny', \App\Models\DocumentDestructionRequest::class)
            );
        @endphp
        @if($canDocumentsMenu)
        <li class="has-submenu {{ request()->routeIs('documents.*') || request()->routeIs('document-versions.*') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/document-text.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.documents') }}</span>
            </a>
            <ul class="submenu list-unstyled ms-4">
                @can('viewAny', \App\Models\Document::class)
                <li class="mt-2">
                    <a href="{{ route('documents.all') }}" class="{{ request()->routeIs('documents.all') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/document-favorite.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.all_documents') }}</span>
                    </a>
                </li>
                @endcan
                @can('viewAny', \App\Models\Category::class)
                <li class="mt-2">
                    <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.index') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/category.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.categories') }}</span>
                    </a>
                </li>
                 @endcan
                {{-- Optional: versions page kept hidden for now
                @can('viewAny', \App\Models\DocumentVersion::class)
                <li class="mt-2">
                    <a href="{{ route('document-versions.index') }}" class="{{ request()->routeIs('document-versions.index') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/document-favorite.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.versions') }}</span>
                    </a>
                </li>
                @endcan
                --}}
            </ul>
        </li>
        @endif

        @canany(['approve','decline'], \App\Models\Document::class)
        <li class="{{ request()->routeIs('documents.status') ? 'active' : '' }}">
            <a href="{{ route('documents.status') }}">
                <img src="{{ asset('assets/template/verify.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.approvals') }}</span>
            </a>
        </li>
        @endcanany
        
        <li class="{{ request()->routeIs('notifications') ? 'active' : '' }}">
            <a href="{{ route('notifications') }}">
                <img src="{{ asset('assets/template/notification.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.notifications') }}</span>
            </a>
        </li>


        @php
            $canSettingsMenu = $u && (
                $u->can('viewAny', \App\Models\Department::class)
                || $u->can('viewAny', \App\Models\Tag::class)
                || $u->can('viewAny', \App\Models\PhysicalLocation::class)
            );
        @endphp
        @if($canSettingsMenu)
        <li class="has-submenu {{ request()->routeIs('departments.*') || request()->routeIs('tags.*') || request()->routeIs('categories.*') || request()->routeIs('physical-locations.*') || request()->routeIs('reports.*') || request()->routeIs('storage.overview') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/setting-2.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.settings') }}</span>
            </a>
            <ul class="submenu list-unstyled ms-4">
                @if($u && ($u->hasRole('master') || $u->hasRole('Super Administrator') || $u->hasRole('admin')))
                    <li class="mt-2">
                        <a href="{{ route('departments.index') }}" class="{{ request()->routeIs('departments.index') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/brifecase-tick.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.structures') }}</span>
                        </a>
                    </li>
                @endif

                {{-- Tags: Access to everyone --}}
                <li class="mt-2">
                    <a href="{{ route('tags.index') }}" class="{{ request()->routeIs('tags.index') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/star.svg') }}" class="me-2" />
                        <span class="sidebar-text">{{ ui_t('nav.tags') }}</span>
                    </a>
                </li>

                @if($u && !$u->hasRole('user'))
                    <li class="mt-2">
                        <a href="{{ route('physical-locations.index') }}" class="{{ request()->routeIs('physical-locations.index') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/Flags.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.physical_location') }}</span>
                        </a>
                    </li>
                @endif

                @if($u && !$u->hasRole('user'))
                    <li class="mt-2">
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/document-favorite.svg') }}" class="me-2" />
                            <span class="sidebar-text">{{ ui_t('nav.reports') }}</span>
                        </a>
                    </li>
                @endif

                @if($u && ($u->hasRole('master') || $u->hasRole('Super Administrator')))
                    <li class="mt-2">
                        <a href="{{ route('storage.overview') }}" class="{{ request()->routeIs('storage.overview') ? 'active' : '' }}">
                            <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-2" />
                            <span class="sidebar-text">Storage &amp; Server Space</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
        @endif



        @php
            $canViewUsers = $u && !$u->hasRole('user');
            $canViewRoles = $u && $u->hasRole('master');
        @endphp
        @if($canViewUsers && !$canViewRoles)
        <li class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
            <a href="{{ route('users.index') }}">
                <img src="{{ asset('assets/template/user.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.users') }}</span>
            </a>
        </li>
        @elseif($canViewUsers || $canViewRoles)
        <li class="has-submenu {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'active' : '' }}">
            <a href="#" class="menu-toggle">
                <img src="{{ asset('assets/template/user.svg') }}" class="me-3" />
                <span class="sidebar-text">{{ ui_t('nav.users') }} & {{ ui_t('nav.roles') }}</span>
            </a>
            <ul class="submenu list-unstyled ms-4">
                @can('viewAny', \App\Models\User::class)
                <li class="mt-2"><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') ? 'active' : '' }}"><img src="{{ asset('assets/template/user.svg') }}" class="me-2" /><span class="sidebar-text">{{ ui_t('nav.users') }}</span></a></li>
                @endcan
                @can('viewAny', \Spatie\Permission\Models\Role::class)
                <li class="mt-2"><a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.index') ? 'active' : '' }}"><img src="{{ asset('assets/template/brifecase-tick.svg') }}" class="me-2" /><span class="sidebar-text">{{ ui_t('nav.roles') }}</span></a></li>
                @endcan
            </ul>
        </li>
        @endif

        @if($u && !$u->hasRole('user'))
            @php
                $canDestruction = $u && $u->hasAnyRole(['master','Super Administrator','super administrator','Admin de pole','admin de pôle']);
            @endphp
            <li class="has-submenu {{ request()->routeIs('users.logs') || request()->routeIs('documents.destructions') ? 'active' : '' }}">
                <a href="#" class="menu-toggle">
                    <img src="{{ asset('assets/template/rotate-left.svg') }}" class="me-3" />
                    <span class="sidebar-text">{{ ui_t('nav.audit') }}</span>
                </a>
                <ul class="submenu list-unstyled ms-4">
                    <li class="mt-2">
                        <a href="{{ route('users.logs') }}" class="{{ request()->routeIs('users.logs') ? 'active' : '' }}">
                            <span class="sidebar-text">{{ ui_t('pages.activity_log.activity_log') ?? 'User Logs' }}</span>
                        </a>
                    </li>
                    @if($canDestruction)
                    <li class="mt-2">
                        <a href="{{ route('documents.destructions') }}" class="{{ request()->routeIs('documents.destructions') ? 'active' : '' }}">
                            <span class="sidebar-text">{{ ui_t('nav.destruction') }}</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
        @endif


        @if(auth()->user()?->hasRole('master'))
            @can('viewAny', \App\Models\OcrJob::class)
            <li class="{{ request()->routeIs('ocr-jobs.index') ? 'active' : '' }}">
                <a href="{{ route('ocr-jobs.index') }}">
                    <img src="{{ asset('assets/template/eye.svg') }}" class="me-3" />
                    <span class="sidebar-text">{{ ui_t('nav.ocr') }}</span>
                </a>
            </li>
            @endcan

            @can('viewAny', \App\Models\UiTranslation::class)
            <li class="{{ request()->routeIs('ui-translations.index') ? 'active' : '' }}">
                <a href="{{ route('ui-translations.index') }}">
                    <img src="{{ asset('assets/template/setting-4.svg') }}" class="me-3" />
                    <span class="sidebar-text">{{ ui_t('nav.localization') }}</span>
                </a>
            </li>
            @endcan
        @endif
    </ul>

    <div class="bottom-icons text-center mt-auto">
        <!-- <a href="{{ route('home') }}" class="d-block mb-2">
            <img src="{{ asset('assets/template/Frame 2078547825 1.svg') }}" alt="logo" class="collapsed-only"/>
        </a> -->
        
        <a href="#" id="toggleSidebar"><img src="{{ asset('assets/template/elements.svg') }}" /><span class="sidebar-text" id="toggleText" data-expand-text="{{ ui_t('nav.expand') }}" data-collapse-text="{{ ui_t('nav.collapse') }}">{{ ui_t('nav.collapse') }} </span></a>
    </div>
</nav>
