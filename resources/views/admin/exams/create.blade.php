@extends('layouts.app')

@section('title', 'Create Examination')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-plus-circle text-primary me-2"></i> Create Examination</h3>
        <p class="text-muted mb-0">Configure test parameters, timer duration, anti-cheat policy, and scoring rules.</p>
    </div>
    <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Exams
    </a>
</div>

<form action="{{ route('admin.exams.store') }}" method="POST">
    @csrf

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
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required placeholder="e.g. Advanced PostgreSQL 18 Engineering Certification">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unique Exam Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', 'EXAM-' . strtoupper(bin2hex(random_bytes(3)))) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subject / Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Select Category...</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Initial Status</label>
                            <select name="status" class="form-select">
                                <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published (Active)</option>
                                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Inactive)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief overview of the exam syllabus and objectives...">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Candidate Instructions</label>
                            <textarea name="instructions" class="form-control" rows="3" placeholder="Instructions displayed on pre-exam lobby screen...">{{ old('instructions', "1. Read each question carefully before submitting responses.\n2. Do not switch tabs or exit fullscreen.\n3. The system autosaves your answers constantly.") }}</textarea>
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
                                <input class="form-check-input" type="checkbox" name="enforce_fullscreen" id="enforce_fullscreen" value="1" {{ old('enforce_fullscreen', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="enforce_fullscreen">Enforce Fullscreen Mode</label>
                                <small class="text-muted d-block">Prompts candidate into fullscreen and logs violations if exited.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_submit_on_violation" id="auto_submit_on_violation" value="1" {{ old('auto_submit_on_violation', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="auto_submit_on_violation">Auto-Submit on Max Violations</label>
                                <small class="text-muted d-block">Automatically locks & submits test if violation limit exceeded.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Permitted Violations</label>
                            <input type="number" name="max_violations" class="form-control" value="{{ old('max_violations', 3) }}" min="1" max="20" required>
                            <small class="text-muted">Count of tab switches or window blurs permitted before termination.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scoring, Timing & Behavior -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-clock-history me-1 text-primary"></i> Duration & Scoring
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Duration (Minutes) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', 60) }}" min="1" max="600" required>
                            <span class="input-group-text">mins</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Passing Score (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.5" name="pass_percentage" class="form-control" value="{{ old('pass_percentage', 60.0) }}" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total Target Marks <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="total_marks" class="form-control" value="{{ old('total_marks', 100) }}" min="1" required>
                    </div>

                    <hr>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="negative_marking" id="negative_marking" value="1" {{ old('negative_marking') ? 'checked' : '' }} onchange="toggleNegativeRate(this)">
                        <label class="form-check-label fw-semibold" for="negative_marking">Enable Negative Marking</label>
                        <small class="text-muted d-block">Deducts points for incorrect answers.</small>
                    </div>

                    <div class="mb-3" id="negative_rate_box" style="display: {{ old('negative_marking') ? 'block' : 'none' }};">
                        <label class="form-label fw-semibold">Default Deduction Rate</label>
                        <input type="number" step="0.05" name="negative_mark_rate" class="form-control" value="{{ old('negative_mark_rate', 0.25) }}" min="0">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-sliders me-1 text-primary"></i> CAT Test Delivery Options
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="shuffle_questions" id="shuffle_questions" value="1" {{ old('shuffle_questions', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="shuffle_questions">Randomize Question Order</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="shuffle_options" id="shuffle_options" value="1" {{ old('shuffle_options', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="shuffle_options">Randomize Answer Choices</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="show_result_instant" id="show_result_instant" value="1" {{ old('show_result_instant', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="show_result_instant">Instant Scorecard on Submit</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="allow_review" id="allow_review" value="1" {{ old('allow_review', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="allow_review">Allow Question Rationale Review</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-check-circle me-1"></i> Save & Continue to Questions
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
