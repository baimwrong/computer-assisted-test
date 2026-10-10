@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Examination Operations Command</h2>
        <p class="text-muted mb-0">Computer Assisted Test system monitoring and management overview.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Bulk Import
        </a>
        <a href="{{ route('admin.exams.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Exam
        </a>
        <a href="{{ route('proctor.monitor') }}" class="btn btn-danger">
            <i class="bi bi-broadcast me-1"></i> Live Proctoring
        </a>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Total Exams</span>
                <h3 class="fw-bold mb-0 mt-1">{{ $stats['total_exams'] }}</h3>
                <small class="text-success"><i class="bi bi-check-circle me-1"></i>{{ $stats['active_exams'] }} published</small>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-journal-check"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Question Bank</span>
                <h3 class="fw-bold mb-0 mt-1">{{ $stats['total_questions'] }}</h3>
                <small class="text-muted">Multi-type objective pool</small>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-patch-question-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Live Active Sessions</span>
                <h3 class="fw-bold mb-0 mt-1 text-danger">{{ $stats['active_sessions'] }}</h3>
                <small class="text-danger fw-semibold"><i class="bi bi-broadcast me-1"></i>In progress right now</small>
            </div>
            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-person-workspace"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Candidates & Pass Rate</span>
                <h3 class="fw-bold mb-0 mt-1">{{ $stats['pass_rate'] }}%</h3>
                <small class="text-muted">{{ $stats['total_candidates'] }} registered candidates</small>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-award-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Section: Recent Attempts & System Status -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Candidate Attempts</h5>
                <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-outline-primary">View Analytics</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Candidate</th>
                                <th>Exam</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Result</th>
                                <th>Started</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAttempts as $att)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $att->user->name }}</div>
                                        <small class="text-muted">{{ $att->user->student_id ?? $att->user->email }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $att->exam->title }}</span>
                                    </td>
                                    <td>
                                        @if ($att->status === 'in_progress')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Testing</span>
                                        @elseif ($att->status === 'completed')
                                            <span class="badge bg-success"><i class="bi bi-check-all me-1"></i> Completed</span>
                                        @elseif ($att->status === 'timed_out')
                                            <span class="badge bg-secondary"><i class="bi bi-alarm me-1"></i> Timed Out</span>
                                        @else
                                            <span class="badge bg-danger"><i class="bi bi-shield-x me-1"></i> Violations</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $att->score }}</strong> <small class="text-muted">({{ $att->percentage }}%)</small>
                                    </td>
                                    <td>
                                        @if ($att->status === 'in_progress')
                                            <span class="text-muted">-</span>
                                        @elseif ($att->is_passed)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">PASSED</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">FAILED</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $att->started_at->diffForHumans() }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No test attempts logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status & Quick Actions -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-hdd-network-fill me-2 text-primary"></i> CAT Architecture Telemetry
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-filetype-php me-2 text-primary"></i> Framework</span>
                        <span class="badge bg-primary">Laravel 13</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-database me-2 text-info"></i> Database</span>
                        <span class="badge bg-info text-dark">PostgreSQL 18</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-wifi-off me-2 text-success"></i> Offline Vendor Assets</span>
                        <span class="badge bg-success">100% Downloaded Locally</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-shield-lock me-2 text-danger"></i> Anti-Cheat Subsystem</span>
                        <span class="badge bg-danger">Active (Tab/Blur Hook)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-arrow-repeat me-2 text-warning"></i> Autosave Asynchronous</span>
                        <span class="badge bg-warning text-dark">15s Local Sync</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 bg-primary text-white p-3">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-cloud-arrow-up-fill fs-1 text-warning"></i>
                <div>
                    <h5 class="fw-bold mb-1">Bulk Import Ready</h5>
                    <p class="small mb-2 text-light">Quickly import hundreds of questions or candidates via standard CSV.</p>
                    <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-light btn-sm fw-semibold text-primary">
                        Open Bulk Import Center <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
