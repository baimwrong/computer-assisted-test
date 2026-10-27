@extends('layouts.app')

@section('title', 'Candidate Profile - ' . $candidate->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i> Candidate Record: {{ $candidate->name }}</h3>
        <p class="text-muted mb-0">Student ID: <strong>{{ $candidate->student_id ?? 'N/A' }}</strong> | Email: {{ $candidate->email }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.candidates.edit', $candidate) }}" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i> Edit / Reset Password
        </a>
        <a href="{{ route('admin.candidates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-person me-1"></i> Account Details
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Full Name</span>
                        <strong>{{ $candidate->name }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Email</span>
                        <span>{{ $candidate->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Registration ID</span>
                        <span class="font-monospace">{{ $candidate->student_id ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Phone</span>
                        <span>{{ $candidate->phone ?? '-' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Account Status</span>
                        <span class="badge bg-{{ $candidate->is_active ? 'success' : 'danger' }}">{{ $candidate->is_active ? 'Active' : 'Deactivated' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Joined</span>
                        <span>{{ $candidate->created_at->format('M d, Y') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-clock-history me-1"></i> Examination Attempt History
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Exam Title</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Status</th>
                                <th>Violations</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($candidate->examAttempts as $att)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $att->exam->title }}</div>
                                        <small class="text-muted font-monospace">{{ $att->exam->code }}</small>
                                    </td>
                                    <td><strong>{{ $att->score }}</strong></td>
                                    <td>{{ $att->percentage }}%</td>
                                    <td>
                                        @if ($att->is_passed)
                                            <span class="badge bg-success">PASSED</span>
                                        @else
                                            <span class="badge bg-danger">FAILED</span>
                                        @endif
                                    </td>
                                    <td>{{ $att->violation_count }}</td>
                                    <td><small class="text-muted">{{ $att->started_at->format('M d, Y H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No test attempts logged for this candidate.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
