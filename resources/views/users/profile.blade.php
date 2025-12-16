@extends('layouts.app')

@section('content')
    <div class="">
        <div class="container mt-4">

            <p class="mb-2">{{ ui_t('pages.profile.my_profile') }}</p>

            <!-- Cover -->
            <div class="cover-photo mb-5 position-relative">
                <div class="profile-pic d-flex justify-content-center align-items-center" style="cursor: pointer;">
                    <img
                        id="profilePreview"
                        src="{{ $user->avatar_url }}"
                        class="img-fluid rounded-circle"
                        alt="profile-pic"
                        style="height: 100%; width: 100%; object-fit: cover;"
                    >
                </div>

                <!-- Hidden form for profile image upload -->
                <form id="profileImageForm" method="POST" action="{{ route('users.updateImage', $user->id) }}" enctype="multipart/form-data" class="d-none">
                    @csrf
                    @method('PUT')
                    <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="display: none;">
                </form>
            </div>
            <!-- Name + Position -->
            <div class="text-center mb-4 mt-2">
                <h6 class="mb-1 fw-semibold">{{ $user->full_name }}</h6>
                <p class="text-muted mb-0">{{ $user->roles->first()->name }}</p>
            </div>

            <!-- Personal Information -->
            <div class="card-section bg-transparent position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title">{{ ui_t('pages.profile.personal_info') }}</div>
                    

                </div>
                <div class="row">
                    <div class="col-md-6 ">
                        <div class="label">{{ ui_t('pages.profile.full_name') }}</div>
                        <div class="value">{{ $user->full_name }}</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="label">{{ ui_t('pages.profile.email') }}</div>
                        <div class="value">{{ $user->email }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="label">{{ ui_t('pages.profile.phone') }}</div>
                        <div class="value">{{ $user->phone }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="label">{{ ui_t('pages.profile.structures') }}</div>
                        <div class="value">
                            @if($user->departments->count() > 0)
                                {{ $user->departments->pluck('name')->join(', ') }}
                            @else
                                {{ ui_t('pages.profile.no_structures') }}
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="label">{{ ui_t('pages.profile.role') }}</div>
                        <div class="value">{{ $user->role }}</div>
                    </div>
                    <div class="col-md-6 mb-3 ">
                        <div class="label">{{ ui_t('pages.profile.password') }}</div>
                        <div class="value">******** <br>
                            <a href="#" class="text-primary text-decoration-none" style="font-size: 0.9rem;" id="nextBtn3">{{ ui_t('pages.profile.change_password') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        

        <div class="layer d-none" id="promptLayer3">
            <div class="profile-edit-box">

                <div class="header">
                    <span>{{ ui_t('pages.profile.change_password_title') }}</span>
                    <button class="exit" id="exitProfileBtn3" style="background: none; border: none;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>


                </div>

                <form method="POST" action="{{ route('users.updatePassword', $user->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label for="old_password" class="form-label">{{ ui_t('pages.profile.old_password') }}</label>
                        <input type="password" class="form-control" name="old_password" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">{{ ui_t('pages.profile.new_password') }}</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">{{ ui_t('pages.profile.confirm_new_password') }}</label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-update px-4">{{ ui_t('pages.profile.update') }}</button>
                    </div>
                </form>


            </div>
        </div>
    </div>

    <!-- Script -->
    <script>
        const imageInput = document.getElementById('profileImageInput');
        const previewImg = document.getElementById('profilePreview');
        const form = document.getElementById('profileImageForm');
        const profilePic = document.querySelector('.profile-pic');

        // When container clicked, trigger file input
        profilePic.addEventListener('click', () => {
            imageInput.click();
        });

        // When a file is selected
        imageInput.addEventListener('change', () => {
            const file = imageInput.files[0];
            if (file) {
                previewImg.src = URL.createObjectURL(file);
                form.submit();
            }
        });

    </script>

    <script>
        document.getElementById("exitProfileBtn")?.addEventListener("click", function () {
            document.getElementById("promptLayer").classList.add("d-none");
        });

        document.getElementById("exitProfileBtn2")?.addEventListener("click", function () {
            document.getElementById("promptLayer2").classList.add("d-none");
        });
        document.getElementById("exitProfileBtn3")?.addEventListener("click", function () {
            document.getElementById("promptLayer3").classList.add("d-none");
        });


    </script>


@endsection

