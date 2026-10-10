@extends('layouts.auth')

@section('content')
<div class="card shadow-lg border-0 rounded-4">
    <div class="card-body p-4 p-sm-5">
        <div class="text-center mb-4">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex p-3 mb-3">
                <i class="bi bi-shield-lock-fill fs-1 text-primary"></i>
            </div>
            <h3 class="fw-bold mb-1">CAT Platform</h3>
            <p class="text-muted small">Computer Assisted Test Examination Portal</p>
        </div>

        @include('layouts.partials.alerts')

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email or Student ID</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@domain.com or CAT-2026-001">
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label for="password" class="form-label fw-semibold">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label text-muted small" for="remember">Remember this workstation</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Exam Portal
            </button>
        </form>

        <!-- Quick Demo Switcher for convenience -->
        <div class="border-top pt-3 mt-4 text-center">
            <small class="text-muted d-block mb-2 fw-semibold">Quick Credential Autofill:</small>
            <div class="d-flex justify-content-center gap-1 flex-wrap">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="fillCreds('admin@cat-system.local', 'Admin@2026!')">
                    <i class="bi bi-shield-fill-check"></i> Admin
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="fillCreds('proctor@cat-system.local', 'Proctor@2026!')">
                    <i class="bi bi-broadcast"></i> Proctor
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="fillCreds('candidate1@cat-system.local', 'Candidate@2026!')">
                    <i class="bi bi-person"></i> Candidate
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function fillCreds(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection
