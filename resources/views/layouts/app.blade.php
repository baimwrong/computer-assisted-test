<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CAT Platform') - Computer Assisted Test System</title>

    <!-- Local Offline Assets - No Internet Required -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    @stack('styles')
</head>
<body>
    @include('layouts.partials.navbar')

    <div class="container-fluid flex-grow-1">
        <div class="row">
            @auth
                @if (Auth::user()->isAdmin())
                    <aside class="col-md-3 col-lg-2 p-0 d-none d-md-block">
                        @include('layouts.partials.sidebar')
                    </aside>
                    <main class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                        @include('layouts.partials.alerts')
                        @yield('content')
                    </main>
                @else
                    <main class="col-12 px-md-4 py-4">
                        @include('layouts.partials.alerts')
                        @yield('content')
                    </main>
                @endif
            @else
                <main class="col-12 px-md-4 py-4">
                    @include('layouts.partials.alerts')
                    @yield('content')
                </main>
            @endauth
        </div>
    </div>

    @include('layouts.partials.footer')

    <!-- Local Offline Scripts -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>

    @stack('scripts')
</body>
</html>
