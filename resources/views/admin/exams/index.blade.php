@extends('layouts.app')

@section('title', 'Examination Manager')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-journal-check text-primary me-2"></i> Examination Management</h3>
        <p class="text-muted mb-0">Create, configure, schedule, and supervise computer-assisted tests.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.exams.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Create New Exam
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.exams.index') }}" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by title or exam code..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter"></i> Filter</button>
                <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Exams Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Exam Title & Code</th>
                        <th>Category</th>
                        <th>Duration</th>
                        <th>Questions</th>
                        <th>Passing %</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        <tr>
                            <td>
                                <a href="{{ route('admin.exams.show', $exam) }}" class="fw-bold text-decoration-none text-dark">
                                    {{ $exam->title }}
                                </a>
                                <div class="small text-muted font-monospace">{{ $exam->code }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $exam->category->name }}</span>
                            </td>
                            <td>
                                <i class="bi bi-stopwatch text-muted me-1"></i> {{ $exam->duration_minutes }} mins
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">{{ $exam->questions_count }} Qs</span>
                            </td>
                            <td>
                                {{ $exam->pass_percentage }}%
                            </td>
                            <td>
                                @if ($exam->status === 'published')
                                    <span class="badge bg-success">Published</span>
                                @elseif ($exam->status === 'draft')
                                    <span class="badge bg-warning text-dark">Draft</span>
                                @else
                                    <span class="badge bg-secondary">Archived</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $exam->attempts_count }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-outline-primary" title="Details & Questions">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('admin.exams.export-results', $exam) }}" class="btn btn-outline-success" title="Export Results CSV">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exam? All related questions and attempts will be removed.');">
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
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                                No examinations found. Click "Create New Exam" above to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($exams->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $exams->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
