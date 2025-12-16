<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/L LOGO.svg') }}" type="image/x-icon">
    <!-- Webfont: Inter for consistent Helvetica-like rendering -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>{{ config('app.name', 'Locaged') }}</title>
</head>
<body>
<div class="container-fluid ps-0">
    <div class="row " style="height: 100vh;overflow: hidden;">
        <div class="col-lg-8 col-md-7 d-md-flex d-none p-0">
            <div class="w-100 h-100" style="background: url('{{ \App\Support\Branding::loginImageUrl() }}') no-repeat center center / cover;"></div>
        </div>
        <div class="col-lg-4 col-md-5 col-12 mx-auto  px-5 px-md-0 d-flex align-items-center justify-content-center">
         @yield('content')
        </div>
    </div>
</body>
</html>
