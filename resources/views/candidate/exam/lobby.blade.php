@extends('layouts.app')

@section('title', 'Exam Lobby - ' . $exam->title)

@section('content')
<div class="row">
    <div class="col-lg-9 mx-auto">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-primary text-white p-4">
                <span class="badge bg-warning text-dark font-monospace mb-2">{{ $exam->code }}</span>
                <h3 class="fw-bold mb-1">{{ $exam->title }}</h3>
                <p class="mb-0 text-white-50">Category: {{ $exam->category->name }}</p>
            </div>

            <div class="card-body p-4 p-sm-5">
                <!-- Exam Key Attributes -->
                <div class="row g-3 mb-4 text-center">
                    <div class="col-sm-3">
                        <div class="bg-light p-3 rounded-3 border">
                            <span class="text-muted small d-block">Allocated Time</span>
                            <strong class="fs-5 text-primary"><i class="bi bi-stopwatch me-1"></i>{{ $exam->duration_minutes }} Mins</strong>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="bg-light p-3 rounded-3 border">
                            <span class="text-muted small d-block">Total Questions</span>
                            <strong class="fs-5 text-dark"><i class="bi bi-patch-question me-1"></i>{{ $questionCount }} Qs</strong>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="bg-light p-3 rounded-3 border">
                            <span class="text-muted small d-block">Passing Score</span>
                            <strong class="fs-5 text-success"><i class="bi bi-award me-1"></i>{{ $exam->pass_percentage }}%</strong>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="bg-light p-3 rounded-3 border">
                            <span class="text-muted small d-block">Negative Marking</span>
                            <strong class="fs-5 text-danger">{{ $exam->negative_marking ? 'Active' : 'None' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Rules & Instructions -->
                <div class="mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-checklist text-primary me-2"></i> Official Examination Rules</h5>
                    <div class="p-3 bg-light rounded-3 border text-secondary small lh-lg">
                        {!! nl2br(e($exam->instructions ?? "Please read each question carefully.\nMaintain continuous focus on the testing interface.\nDo not minimize the browser or switch applications.")) !!}
                    </div>
                </div>

                <!-- Anti-Cheat Security Notice -->
                @if ($exam->enforce_fullscreen)
                    <div class="alert alert-warning border-warning d-flex align-items-start gap-3 rounded-3 mb-4">
                        <i class="bi bi-shield-exclamation fs-3 text-warning"></i>
                        <div class="small">
                            <strong>Security & Proctoring Notice:</strong>
                            <p class="mb-1">This examination operates under automated computer proctoring rules:</p>
                            <ul class="mb-0 ps-3">
                                <li>The exam runs in <strong>mandatory full-screen mode</strong>.</li>
                                <li>Exiting full-screen or switching browser tabs will be recorded as a security violation.</li>
                                <li>A maximum of <strong>{{ $exam->max_violations }} violations</strong> is allowed before your test session is terminated.</li>
                                <li>Clipboard copy/cut/paste and right-click context menu are disabled.</li>
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- System Readiness Check -->
                <div class="card border mb-4">
                    <div class="card-body p-3 small">
                        <h6 class="fw-bold mb-2"><i class="bi bi-cpu me-1 text-primary"></i> Workstation Compatibility Verification</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> Browser: <span id="browser-name">Compatible</span>
                            </div>
                            <div class="col-md-4">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> Network: <span>Local / Offline Sync</span>
                            </div>
                            <div class="col-md-4">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> Storage: <span>Active Storage Persistence</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <form action="{{ route('candidate.exam.start', $exam) }}" method="POST">
                    @csrf

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="rules_agreement" required onchange="document.getElementById('start-btn').disabled = !this.checked">
                        <label class="form-check-label fw-semibold" for="rules_agreement">
                            I confirm that I am {{ Auth::user()->name }} (ID: {{ Auth::user()->student_id ?? Auth::id() }}), and I agree to abide by all examination rules and proctoring conditions.
                        </label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('candidate.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Return to Dashboard
                        </a>
                        <button type="submit" id="start-btn" class="btn btn-primary btn-lg px-4 fw-bold shadow" disabled>
                            <i class="bi bi-play-circle-fill me-2"></i> Start Examination Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
