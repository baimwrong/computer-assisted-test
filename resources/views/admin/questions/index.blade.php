@extends('layouts.app')

@section('title', 'Question Bank')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-collection-fill text-primary me-2"></i> Question Bank Repository</h3>
        <p class="text-muted mb-0">Browse and manage questions across exams and subject categories.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Question
        </a>
        <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Bulk Import CSV
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.questions.index') }}" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search question text..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="exam_id" class="form-select">
                    <option value="">All Exams</option>
                    @foreach ($exams as $ex)
                        <option value="{{ $ex->id }}" {{ request('exam_id') == $ex->id ? 'selected' : '' }}>{{ $ex->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="question_type" class="form-select">
                    <option value="">All Question Types</option>
                    <option value="single_choice" {{ request('question_type') === 'single_choice' ? 'selected' : '' }}>Single Choice</option>
                    <option value="multiple_choice" {{ request('question_type') === 'multiple_choice' ? 'selected' : '' }}>Multiple Choice</option>
                    <option value="true_false" {{ request('question_type') === 'true_false' ? 'selected' : '' }}>True / False</option>
                    <option value="short_answer" {{ request('question_type') === 'short_answer' ? 'selected' : '' }}>Short Answer</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter"></i></button>
                <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Questions Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Question</th>
                        <th>Assigned Exam</th>
                        <th>Type</th>
                        <th>Difficulty</th>
                        <th>Points</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $q)
                        <tr>
                            <td class="text-muted fw-bold">{{ $q->id }}</td>
                            <td>
                                <div class="fw-medium">{{ Str::limit($q->question_text, 90) }}</div>
                                <div class="d-flex gap-2 mt-1">
                                    <small class="text-muted"><i class="bi bi-list-check me-1"></i>{{ $q->options->count() }} choices</small>
                                    @if ($q->category)
                                        <small class="badge bg-light text-secondary border">{{ $q->category->name }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($q->exam)
                                    <a href="{{ route('admin.exams.show', $q->exam) }}" class="text-decoration-none fw-semibold">
                                        {{ $q->exam->title }}
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">Global Pool</span>
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
                            <td><strong>{{ $q->points }}</strong></td>
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
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No questions found in repository matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($questions->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $questions->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
