@extends('layouts.app')

@section('title', 'My Examination History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i> My Examination History</h3>
        <p class="text-muted mb-0">Record of all past computer-assisted tests, scores, and certificates.</p>
    </div>
    <a href="{{ route('candidate.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Exam Title & Code</th>
                        <th>Category</th>
                        <th>Date Taken</th>
                        <th>Duration</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Outcome</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $att)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $att->exam->title }}</div>
                                <small class="text-muted font-monospace">{{ $att->exam->code }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $att->exam->category->name }}</span></td>
                            <td><small class="text-muted">{{ $att->started_at->format('M d, Y H:i') }}</small></td>
                            <td>{{ $att->timeSpentFormatted() }}</td>
                            <td><strong>{{ $att->score }}</strong> / {{ $att->exam->total_marks }}</td>
                            <td>{{ $att->percentage }}%</td>
                            <td>
                                @if ($att->is_passed)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> PASSED</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> FAILED</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('candidate.exam.result', $att) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text me-1"></i> Scorecard
                                </a>
                                @if ($att->exam->allow_review)
                                    <a href="{{ route('candidate.exam.review', $att) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye me-1"></i> Review
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No examination history recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($attempts->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $attempts->links() }}
        </div>
    @endif
</div>
@endsection
