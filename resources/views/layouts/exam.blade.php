<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Exam Session') - CAT Platform</title>

    <!-- Local Offline Assets -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        body {
            user-select: none;
            -webkit-user-select: none;
            background-color: #f1f5f9;
        }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column vh-100 overflow-hidden">
    <!-- Top Examination Action Bar -->
    <header class="navbar navbar-dark bg-dark px-3 shadow-sm py-2" style="background: #0f172a !important;">
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-brand m-0 fw-bold fs-6 text-white d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill text-warning"></i>
                <span class="d-none d-sm-inline">CAT Proctored Exam:</span>
                <span class="text-info">@yield('exam-title')</span>
            </span>
            <span id="sync-status-badge" class="sync-badge sync-saved d-none d-md-inline-flex">
                <i class="bi bi-cloud-check-fill"></i> Saved
            </span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Fullscreen Button -->
            <button id="btn-toggle-fullscreen" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1" title="Toggle Fullscreen">
                <i class="bi bi-arrows-fullscreen"></i>
                <span class="d-none d-md-inline">Fullscreen</span>
            </button>

            <!-- Live Timer Widget -->
            <div id="timer-container" class="exam-timer-box">
                <i class="bi bi-stopwatch"></i>
                <span id="timer-display">--:--</span>
            </div>

            <!-- Candidate Profile Pill -->
            <div class="d-none d-lg-flex align-items-center gap-2 text-white border-start ps-3 border-secondary">
                <i class="bi bi-person-badge fs-5 text-warning"></i>
                <div class="lh-1 text-start">
                    <small class="d-block fw-semibold">{{ Auth::user()->name }}</small>
                    <small class="text-muted fs-8">{{ Auth::user()->student_id ?? 'ID: ' . Auth::id() }}</small>
                </div>
            </div>

            <!-- Submit Test Action -->
            <button type="button" class="btn btn-success btn-sm fw-bold px-3 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#confirmSubmitModal">
                <i class="bi bi-check2-circle"></i>
                <span>Submit Exam</span>
            </button>
        </div>
    </header>

    <!-- Main Active Test Area -->
    <main class="flex-grow-1 overflow-auto p-3">
        @yield('content')
    </main>

    <!-- Local Offline Scripts -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/exam-engine.js') }}"></script>

    <script>
        // Fullscreen Toggle Helper
        document.getElementById('btn-toggle-fullscreen')?.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.warn(`Error attempting fullscreen: ${err.message}`);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
