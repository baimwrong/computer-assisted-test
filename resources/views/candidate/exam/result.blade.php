@extends('layouts.app')

@section('title', 'Examination Scorecard - ' . $attempt->exam->title)

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <!-- Main Scorecard Card -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4 print-card">
            <div class="card-header p-4 text-center {{ $attempt->is_passed ? 'bg-success text-white' : 'bg-danger text-white' }}">
                <div class="rounded-circle bg-white text-{{ $attempt->is_passed ? 'success' : 'danger' }} d-inline-flex p-3 mb-2 shadow-sm">
                    <i class="bi bi-{{ $attempt->is_passed ? 'trophy-fill' : 'x-octagon-fill' }} fs-1"></i>
                </div>
                <h2 class="fw-bold mb-1">{{ $attempt->is_passed ? 'CONGRATULATIONS! YOU PASSED' : 'EXAMINATION NOT PASSED' }}</h2>
                <p class="mb-0 text-white-50 fs-6">Official Computer Assisted Test Scorecard</p>
            </div>

            <div class="card-body p-4 p-sm-5">
                <!-- Candidate & Exam Info -->
                <div class="row g-3 mb-4 pb-3 border-bottom">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Candidate Name:</small>
                        <strong class="fs-5">{{ $attempt->user->name }}</strong>
                        <div class="text-muted small">Student ID: {{ $attempt->user->student_id ?? 'CAT-' . $attempt->user->id }}</div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <small class="text-muted d-block">Examination:</small>
                        <strong class="fs-5">{{ $attempt->exam->title }}</strong>
                        <div class="text-muted small">Code: {{ $attempt->exam->code }} | {{ $attempt->exam->category->name }}</div>
                    </div>
                </div>

                <!-- Big Result Badge Matrix -->
                <div class="row g-3 text-center mb-4">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Score Obtained</span>
                            <h3 class="fw-bold mb-0 text-primary">{{ $attempt->score }}</h3>
                            <small class="text-muted">Target: {{ $attempt->exam->total_marks }}</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Percentage</span>
                            <h3 class="fw-bold mb-0 {{ $attempt->is_passed ? 'text-success' : 'text-danger' }}">{{ $attempt->percentage }}%</h3>
                            <small class="text-muted">Required: {{ $attempt->exam->pass_percentage }}%</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Accuracy</span>
                            <h3 class="fw-bold mb-0 text-success">{{ $attempt->correct_answers }}</h3>
                            <small class="text-muted">of {{ $attempt->total_questions }} Questions</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Time Spent</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ $attempt->timeSpentFormatted() }}</h3>
                            <small class="text-muted">Max: {{ $attempt->exam->duration_minutes }}m</small>
                        </div>
                    </div>
                </div>

                <!-- Detailed Breakdown List -->
                <div class="card border mb-4">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-1 text-primary"></i> Performance Telemetry</h6>
                        <div class="row g-2 small">
                            <div class="col-sm-6 d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Questions Answered:</span>
                                <strong>{{ $attempt->answered_questions }} of {{ $attempt->total_questions }}</strong>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Incorrect Answers:</span>
                                <strong class="text-danger">{{ $attempt->incorrect_answers }}</strong>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Proctoring Violations Logged:</span>
                                <strong>{{ $attempt->violation_count }}</strong>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Submission Timestamp:</span>
                                <strong>{{ $attempt->submitted_at ? $attempt->submitted_at->toDateTimeString() : 'N/A' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Actions -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
                    <a href="{{ route('candidate.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-house-door me-1"></i> Return to Dashboard
                    </a>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-dark" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Scorecard
                        </button>

                        @if ($attempt->exam->allow_review)
                            <a href="{{ route('candidate.exam.review', $attempt) }}" class="btn btn-primary fw-semibold">
                                <i class="bi bi-card-checklist me-1"></i> Review Explanations & Answers
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
