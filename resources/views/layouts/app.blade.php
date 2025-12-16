<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  {{--  <meta name="csrf-token" content="{{ csrf_token() }}">--}}

    <title>{{ config('app.name', 'Locaged') }}</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/L LOGO.svg') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles

    

</head>
<body dir="{{ session('rtl') ?  'rtl' : 'ltr' }}">
<div class="wrapper layout-with-sidebar">
    <div class="container-fluid ps-0">
        <!-- Sidebar (fixed) -->
        @include('layouts.sidebar')
        <div id="sidebarOverlay" class="sidebar-overlay"></div>

        <!-- Main Content (pushed by sidebar via CSS) -->
        <div class="main-content">
            <!-- <img
                src="{{ asset('assets/template/logo.svg') }}"
                class="mt-4 mt-md-3 ms-3 ms-md-0"
                alt="LocaGed"
                width="100"
            /> -->
            <!-- Header -->
            @include('layouts.header')
            <x-errors/>
            @yield('content')


        </div>
    </div>
</div>
@yield('scripts')
@livewireScripts



</body>
</html>
