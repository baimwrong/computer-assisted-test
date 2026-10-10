@extends('layouts.app')

@section('title', 'Edit Exam - ' . $exam->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Examination Settings</h3>
        <p class="text-muted mb-0">Modify configuration parameters for <strong>{{ $exam->title }}</strong> ({{ $exam->code }}).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Exam
        </a>
    </div>
</div>

<form action="{{ route('admin.exams.update', $exam) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Basic Information -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Basic Exam Details
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Exam Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $exam->title) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Exam Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $exam->code) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $exam->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="published" {{ old('status', $exam->status) === 'published' ? 'selected' : '' }}>Published (Active)</option>
                                <option value="draft" {{ old('status', $exam->status) === 'draft' ? 'selected' : '' }}>Draft (Inactive)</option>
                                <option value="archived" {{ old('status', $exam->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $exam->description) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Instructions</label>
                            <textarea name="instructions" class="form-control" rows="3">{{ old('instructions', $exam->instructions) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Anti-Cheat & Proctoring Engine Settings -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 fw-bold text-danger">
                    <i class="bi bi-shield-lock-fill me-1"></i> Anti-Cheat & Proctoring Configuration
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enforce_fullscreen" id="enforce_fullscreen" value="1" {{ old('enforce_fullscreen', $exam->enforce_fullscreen) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="enforce_fullscreen">Enforce Fullscreen Mode</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_submit_on_violation" id="auto_submit_on_violation" value="1" {{ old('auto_submit_on_violation', $exam->auto_submit_on_violation) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="auto_submit_on_violation">Auto-Submit on Max Violations</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Permitted Violations</label>
                            <input type="number" name="max_violations" class="form-control" value="{{ old('max_violations', $exam->max_violations) }}" min="1" max="20" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Duration & Scoring -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-clock-history me-1 text-primary"></i> Duration & Scoring
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $exam->duration_minutes) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Passing Score (%)</label>
                        <input type="number" step="0.5" name="pass_percentage" class="form-control" value="{{ old('pass_percentage', $exam->pass_percentage) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total Target Marks</label>
                        <input type="number" step="1" name="total_marks" class="form-control" value="{{ old('total_marks', $exam->total_marks) }}" required>
                    </div>

                    <hr>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="negative_marking" id="negative_marking" value="1" {{ old('negative_marking', $exam->negative_marking) ? 'checked' : '' }} onchange="toggleNegativeRate(this)">
                        <label class="form-check-label fw-semibold" for="negative_marking">Enable Negative Marking</label>
                    </div>

                    <div class="mb-3" id="negative_rate_box" style="display: {{ old('negative_marking', $exam->negative_marking) ? 'block' : 'none' }};">
                        <label class="form-label fw-semibold">Deduction Rate</label>
                        <input type="number" step="0.05" name="negative_mark_rate" class="form-control" value="{{ old('negative_mark_rate', $exam->negative_mark_rate) }}">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-sliders me-1 text-primary"></i> CAT Test Delivery Options
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="shuffle_questions" id="shuffle_questions" value="1" {{ old('shuffle_questions', $exam->shuffle_questions) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="shuffle_questions">Randomize Question Order</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="shuffle_options" id="shuffle_options" value="1" {{ old('shuffle_options', $exam->shuffle_options) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="shuffle_options">Randomize Answer Choices</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="show_result_instant" id="show_result_instant" value="1" {{ old('show_result_instant', $exam->show_result_instant) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="show_result_instant">Instant Scorecard on Submit</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="allow_review" id="allow_review" value="1" {{ old('allow_review', $exam->allow_review) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="allow_review">Allow Question Rationale Review</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-save me-1"></i> Update Exam Configuration
            </button>
        </div>
    </div>
</form>

<script>
    function toggleNegativeRate(el) {
        document.getElementById('negative_rate_box').style.display = el.checked ? 'block' : 'none';
    }
</script>
@endsection
