@extends('layouts.app')

@section('title', 'Candidate Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i> Candidate Directory</h3>
        <p class="text-muted mb-0">Register candidates, monitor participant status, and manage exam credentials.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.candidates.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Register Candidate
        </a>
        <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Bulk Import Candidates
        </a>
    </div>
</div>

<!-- Search -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.candidates.index') }}" class="row g-3">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control" placeholder="Search by name, student registration ID, or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search me-1"></i> Search</button>
                <a href="{{ route('admin.candidates.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Candidates Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Candidate Profile</th>
                        <th>Student / Reg ID</th>
                        <th>Contact</th>
                        <th>Tests Taken</th>
                        <th>Passed Tests</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($candidates as $cand)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        {{ strtoupper(substr($cand->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $cand->name }}</div>
                                        <small class="text-muted">{{ $cand->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark font-monospace border">{{ $cand->student_id ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $cand->phone ?? '-' }}</td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold">{{ $cand->total_attempts }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">{{ $cand->passed_attempts }}</span>
                            </td>
                            <td>
                                @if ($cand->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Deactivated</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <form action="{{ route('admin.candidates.toggle-status', $cand) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning" title="{{ $cand->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.candidates.destroy', $cand) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this candidate account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No candidates found in directory.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($candidates->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $candidates->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
