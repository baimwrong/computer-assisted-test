@extends('layouts.app')

@section('title', 'Candidate Test Portal')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Welcome, {{ Auth::user()->name }}!</h3>
        <p class="text-muted mb-0">Candidate Registration ID: <strong>{{ Auth::user()->student_id ?? 'CAT-' . Auth::id() }}</strong></p>
    </div>
</div>

<!-- Active Ongoing Attempt Banner if present -->
@if ($activeAttempt)
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-danger bg-opacity-10 border-start border-danger border-4">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-danger mb-2"><i class="bi bi-broadcast me-1"></i> TEST SESSION IN PROGRESS</span>
                <h4 class="fw-bold mb-1 text-danger">{{ $activeAttempt->exam->title }}</h4>
                <p class="text-muted mb-0">
                    Remaining Time: <strong class="font-monospace text-dark">{{ gmdate('H:i:s', max(0, $activeAttempt->remainingSeconds())) }}</strong> | 
                    Questions Completed: {{ $activeAttempt->answered_questions }} of {{ $activeAttempt->total_questions }}
                </p>
            </div>
            <a href="{{ route('candidate.exam.take', $activeAttempt) }}" class="btn btn-danger btn-lg px-4 fw-bold shadow-sm">
                <i class="bi bi-play-circle-fill me-2"></i> Resume Active Exam Now
            </a>
        </div>
    </div>
@endif

<!-- Quick Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Tests Completed</span>
                <h3 class="fw-bold mb-0 mt-1">{{ $stats['total_taken'] }}</h3>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-journal-check"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Passed Tests</span>
                <h3 class="fw-bold mb-0 mt-1 text-success">{{ $stats['passed'] }}</h3>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-award-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Available Assessments</span>
                <h3 class="fw-bold mb-0 mt-1 text-info">{{ $availableExams->count() }}</h3>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-laptop-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- Available Exams Grid -->
<h4 class="fw-bold mb-3"><i class="bi bi-laptop text-primary me-2"></i> Available Examinations</h4>
<div class="row g-4 mb-5">
    @forelse ($availableExams as $exam)
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column">
                <div class="card-header bg-white py-3 border-0">
                    <span class="badge bg-light text-dark border">{{ $exam->category->name }}</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle float-end font-monospace">{{ $exam->code }}</span>
                </div>
                <div class="card-body flex-grow-1">
                    <h5 class="fw-bold mb-2">{{ $exam->title }}</h5>
                    <p class="text-muted small mb-3">{{ Str::limit($exam->description, 100) }}</p>

                    <div class="d-flex justify-content-between small text-muted border-top pt-2">
                        <span><i class="bi bi-stopwatch me-1"></i> {{ $exam->duration_minutes }} Mins</span>
                        <span><i class="bi bi-patch-question me-1"></i> {{ $exam->questions_count }} Questions</span>
                        <span><i class="bi bi-check-circle me-1"></i> {{ $exam->pass_percentage }}% Pass</span>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pb-3 pt-0">
                    @if ($exam->isUpcoming())
                        <button class="btn btn-outline-secondary w-100 fw-semibold" disabled>
                            <i class="bi bi-calendar-event me-1"></i> Opens {{ $exam->start_time->format('M d, H:i') }}
                        </button>
                    @elseif ($exam->isExpiredSchedule())
                        <button class="btn btn-outline-danger w-100 fw-semibold" disabled>
                            <i class="bi bi-x-circle me-1"></i> Concluded
                        </button>
                    @else
                        <a href="{{ route('candidate.exam.lobby', $exam) }}" class="btn btn-outline-primary w-100 fw-semibold">
                            Enter Exam Lobby <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-5 text-center text-muted">
                <i class="bi bi-journal-x fs-1 mb-2"></i>
                <h5>No Active Examinations Available</h5>
                <p class="small mb-0">Check back later or contact your supervisor when an examination schedule is published.</p>
            </div>
        </div>
    @endforelse
</div>

<!-- Recent Test History -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Test History</h5>
        <a href="{{ route('candidate.history') }}" class="btn btn-sm btn-outline-primary">View Full History</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Exam Title</th>
                        <th>Date Taken</th>
                        <th>Duration</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Outcome</th>
                        <th class="text-end">Scorecard</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAttempts as $att)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $att->exam->title }}</div>
                                <small class="text-muted font-monospace">{{ $att->exam->code }}</small>
                            </td>
                            <td><small class="text-muted">{{ $att->started_at->format('M d, Y H:i') }}</small></td>
                            <td>{{ $att->timeSpentFormatted() }}</td>
                            <td><strong>{{ $att->score }}</strong></td>
                            <td>{{ $att->percentage }}%</td>
                            <td>
                                @if ($att->is_passed)
                                    <span class="badge bg-success">PASSED</span>
                                @else
                                    <span class="badge bg-danger">FAILED</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('candidate.exam.result', $att) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text me-1"></i> Scorecard
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">You have not completed any examinations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
