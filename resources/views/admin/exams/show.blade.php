@extends('layouts.app')

@section('title', $exam->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="fw-bold mb-0">{{ $exam->title }}</h3>
            <span class="badge bg-light text-dark font-monospace border fs-7">{{ $exam->code }}</span>
            @if ($exam->status === 'published')
                <span class="badge bg-success">Published</span>
            @else
                <span class="badge bg-warning text-dark">{{ ucfirst($exam->status) }}</span>
            @endif
        </div>
        <p class="text-muted mb-0">Category: <strong>{{ $exam->category->name }}</strong> | Created by: {{ $exam->creator->name ?? 'Admin' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.questions.create', ['exam_id' => $exam->id]) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Question
        </a>
        <a href="{{ route('admin.bulk-import.index', ['exam_id' => $exam->id]) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Bulk Import Questions
        </a>
        <a href="{{ route('admin.exams.export-results', $exam) }}" class="btn btn-outline-dark">
            <i class="bi bi-download me-1"></i> Export Results
        </a>
        <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <form action="{{ route('admin.exams.toggle-status', $exam) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-warning">
                <i class="bi bi-toggle-on me-1"></i> {{ $exam->is_active ? 'Unpublish' : 'Publish' }}
            </button>
        </form>
    </div>
</div>

<!-- Exam Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3">
            <span class="text-muted small text-uppercase fw-semibold">Questions Count</span>
            <h4 class="fw-bold mb-0 mt-1">{{ $exam->questions_count }} Qs</h4>
            <small class="text-muted">Total Marks: {{ $exam->total_marks }}</small>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3">
            <span class="text-muted small text-uppercase fw-semibold">Duration & Pass Mark</span>
            <h4 class="fw-bold mb-0 mt-1">{{ $exam->duration_minutes }} Mins</h4>
            <small class="text-muted">Passing: {{ $exam->pass_percentage }}%</small>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3">
            <span class="text-muted small text-uppercase fw-semibold">Candidates Attempted</span>
            <h4 class="fw-bold mb-0 mt-1">{{ $stats['total_candidates'] }}</h4>
            <small class="text-success">{{ $stats['passed'] }} Passed / {{ $stats['failed'] }} Failed</small>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card p-3">
            <span class="text-muted small text-uppercase fw-semibold">Average Score</span>
            <h4 class="fw-bold mb-0 mt-1">{{ $stats['avg_score'] }}</h4>
            <small class="text-muted">Highest: {{ $stats['highest_score'] }}</small>
        </div>
    </div>
</div>

<!-- Tabs: Questions & Attempts -->
<ul class="nav nav-pills mb-4" id="examTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="questions-tab" data-bs-toggle="pill" data-bs-target="#tab-questions" type="button" role="tab">
            <i class="bi bi-patch-question me-1"></i> Questions List ({{ $exam->questions_count }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="attempts-tab" data-bs-toggle="pill" data-bs-target="#tab-attempts" type="button" role="tab">
            <i class="bi bi-people me-1"></i> Candidate Submissions ({{ $exam->attempts_count }})
        </button>
    </li>
</ul>

<div class="tab-content" id="examTabsContent">
    <!-- Questions Tab -->
    <div class="tab-pane fade show active" id="tab-questions" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Examination Questions</h5>
                <a href="{{ route('admin.questions.create', ['exam_id' => $exam->id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Question
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Question Prompt</th>
                                <th>Type</th>
                                <th>Difficulty</th>
                                <th>Points</th>
                                <th>Options Count</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exam->questions as $idx => $q)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-medium">{{ Str::limit($q->question_text, 80) }}</div>
                                        @if ($q->explanation)
                                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>{{ Str::limit($q->explanation, 60) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ ucwords(str_replace('_', ' ', $q->question_type)) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $q->difficulty === 'easy' ? 'success' : ($q->difficulty === 'medium' ? 'warning text-dark' : 'danger') }}">
                                            {{ ucfirst($q->difficulty) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $q->points }}</strong>
                                        @if ($q->negative_points > 0)
                                            <small class="text-danger">(-{{ $q->negative_points }})</small>
                                        @endif
                                    </td>
                                    <td>{{ $q->options->count() }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.questions.edit', $q) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.questions.destroy', $q) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this question?');">
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
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-patch-question fs-1 d-block mb-2"></i>
                                        No questions assigned to this exam yet.
                                        <div class="mt-2">
                                            <a href="{{ route('admin.questions.create', ['exam_id' => $exam->id]) }}" class="btn btn-sm btn-primary">Add First Question</a>
                                            <span class="mx-2 text-muted">or</span>
                                            <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-sm btn-outline-success">Bulk Import Questions CSV</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Attempts Tab -->
    <div class="tab-pane fade" id="tab-attempts" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Candidate Submissions</h5>
                <a href="{{ route('admin.exams.export-results', $exam) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-download me-1"></i> Export Results CSV
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Candidate</th>
                                <th>Status</th>
                                <th>Answered</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Result</th>
                                <th>Violations</th>
                                <th>Duration</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attempts as $att)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $att->user->name }}</div>
                                        <small class="text-muted">{{ $att->user->student_id ?? $att->user->email }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $att->status === 'completed' ? 'success' : ($att->status === 'in_progress' ? 'warning text-dark' : 'danger') }}">
                                            {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $att->answered_questions }} / {{ $att->total_questions }}</td>
                                    <td><strong>{{ $att->score }}</strong></td>
                                    <td>{{ $att->percentage }}%</td>
                                    <td>
                                        @if ($att->status === 'in_progress')
                                            <span class="text-muted">In Progress</span>
                                        @elseif ($att->is_passed)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">PASSED</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">FAILED</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($att->violation_count > 0)
                                            <span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $att->violation_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>{{ $att->timeSpentFormatted() }}</td>
                                    <td><small class="text-muted">{{ $att->submitted_at ? $att->submitted_at->toDateTimeString() : '-' }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">No candidate attempts for this examination yet.</td>
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
    </div>
</div>
@endsection
