@extends('layouts.app')

@section('title', 'Add Question')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-patch-question-fill text-primary me-2"></i> Add Question to Bank</h3>
        <p class="text-muted mb-0">Create multiple choice, true/false, or short answer assessment questions.</p>
    </div>
    <a href="{{ $selectedExamId ? route('admin.exams.show', $selectedExamId) : route('admin.questions.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('admin.questions.store') }}" method="POST">
    @csrf

    <div class="row g-4">
        <!-- Question Properties -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-file-text me-1 text-primary"></i> Question Content
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Question Prompt <span class="text-danger">*</span></label>
                        <textarea name="question_text" class="form-control" rows="4" required placeholder="Type the question clearly...">{{ old('question_text') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Question Type <span class="text-danger">*</span></label>
                        <select name="question_type" id="question_type" class="form-select" onchange="switchQuestionType(this.value)" required>
                            <option value="single_choice" {{ old('question_type') === 'single_choice' ? 'selected' : '' }}>Multiple Choice (Single Correct Answer)</option>
                            <option value="multiple_choice" {{ old('question_type') === 'multiple_choice' ? 'selected' : '' }}>Multiple Choice (Multiple Correct Answers)</option>
                            <option value="true_false" {{ old('question_type') === 'true_false' ? 'selected' : '' }}>True / False</option>
                            <option value="short_answer" {{ old('question_type') === 'short_answer' ? 'selected' : '' }}>Short Answer / Fill in Blank</option>
                        </select>
                    </div>

                    <!-- Options Container: Single Choice / Multiple Choice -->
                    <div id="choices-container">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Options & Answer Key</label>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addOptionRow()">
                                <i class="bi bi-plus-circle me-1"></i> Add Choice
                            </button>
                        </div>
                        <p class="text-muted small">Select the radio / checkbox next to the choice to mark it as the correct answer.</p>

                        <div id="options-list">
                            @foreach (['A', 'B', 'C', 'D'] as $idx => $letter)
                                <div class="input-group mb-2 option-row" data-index="{{ $idx }}">
                                    <span class="input-group-text bg-light fw-bold letter-badge">{{ $letter }}</span>
                                    <div class="input-group-text bg-white">
                                        <input class="form-check-input choice-selector mt-0" type="radio" name="correct_options[]" value="{{ $idx }}" id="choice_check_{{ $idx }}" {{ $idx === 0 ? 'checked' : '' }}>
                                    </div>
                                    <input type="text" name="options[]" class="form-control" placeholder="Option {{ $letter }} text..." required>
                                    @if ($idx >= 2)
                                        <button type="button" class="btn btn-outline-danger" onclick="removeOptionRow(this)" title="Remove option">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Container: True / False -->
                    <div id="tf-container" style="display: none;">
                        <label class="form-label fw-semibold">Select Correct Answer</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tf_correct" id="tf_true" value="true" checked>
                                <label class="form-check-label fw-bold text-success" for="tf_true">True</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tf_correct" id="tf_false" value="false">
                                <label class="form-check-label fw-bold text-danger" for="tf_false">False</label>
                            </div>
                        </div>
                    </div>

                    <!-- Container: Short Answer -->
                    <div id="short-answer-container" style="display: none;">
                        <label class="form-label fw-semibold">Accepted Correct Answer(s)</label>
                        <input type="text" name="short_answers" class="form-control" placeholder="e.g. SELECT | Select Statement (Separate alternatives with pipe |)">
                        <small class="text-muted">Candidate responses are matched case-insensitively.</small>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-semibold">Explanation / Rationale (Shown upon result review)</label>
                        <textarea name="explanation" class="form-control" rows="3" placeholder="Provide background rationale for the correct answer...">{{ old('explanation') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta & Assignment -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-tag me-1 text-primary"></i> Assignment & Scoring
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Examination</label>
                        <select name="exam_id" class="form-select">
                            <option value="">-- Global Pool (No specific exam) --</option>
                            @foreach ($exams as $ex)
                                <option value="{{ $ex->id }}" {{ (old('exam_id', $selectedExamId) == $ex->id) ? 'selected' : '' }}>{{ $ex->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject / Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category...</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Points / Marks <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="points" class="form-control" value="{{ old('points', 1.0) }}" min="0.1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Negative Points Deduction</label>
                        <input type="number" step="0.25" name="negative_points" class="form-control" value="{{ old('negative_points', 0.0) }}" min="0">
                        <small class="text-muted">Applied if the exam has negative marking enabled.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Difficulty Level</label>
                        <select name="difficulty" class="form-select">
                            <option value="easy" {{ old('difficulty') === 'easy' ? 'selected' : '' }}>Easy</option>
                            <option value="medium" {{ old('difficulty', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="hard" {{ old('difficulty') === 'hard' ? 'selected' : '' }}>Hard</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mt-2">
                        <i class="bi bi-save me-1"></i> Save Question
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    function switchQuestionType(type) {
        const choices = document.getElementById('choices-container');
        const tf = document.getElementById('tf-container');
        const sa = document.getElementById('short-answer-container');
        const selectors = document.querySelectorAll('.choice-selector');
        const optionInputs = document.querySelectorAll('input[name="options[]"]');

        choices.style.display = 'none';
        tf.style.display = 'none';
        sa.style.display = 'none';

        if (type === 'single_choice' || type === 'multiple_choice') {
            choices.style.display = 'block';
            optionInputs.forEach(i => {
                i.disabled = false;
                i.required = true;
            });
            selectors.forEach(s => {
                s.disabled = false;
                s.type = (type === 'multiple_choice' ? 'checkbox' : 'radio');
            });
        } else {
            optionInputs.forEach(i => {
                i.disabled = true;
                i.required = false;
            });
            selectors.forEach(s => s.disabled = true);

            if (type === 'true_false') {
                tf.style.display = 'block';
            } else if (type === 'short_answer') {
                sa.style.display = 'block';
            }
        }
    }

    function reindexOptions() {
        const rows = document.querySelectorAll('#options-list .option-row');
        const isMultiple = document.getElementById('question_type').value === 'multiple_choice';

        rows.forEach((row, idx) => {
            const letter = alphabet[idx] || String.fromCharCode(65 + idx);
            row.dataset.index = idx;
            row.querySelector('.letter-badge').textContent = letter;
            
            const selector = row.querySelector('.choice-selector');
            selector.value = idx;
            selector.id = `choice_check_${idx}`;
            selector.type = isMultiple ? 'checkbox' : 'radio';

            const textInput = row.querySelector('input[name="options[]"]');
            textInput.placeholder = `Option ${letter} text...`;
        });
    }

    function addOptionRow() {
        const list = document.getElementById('options-list');
        const count = list.querySelectorAll('.option-row').length;
        if (count >= 6) {
            alert('A maximum of 6 answer choices is supported.');
            return;
        }

        const letter = alphabet[count] || String.fromCharCode(65 + count);
        const isMultiple = document.getElementById('question_type').value === 'multiple_choice';

        const row = document.createElement('div');
        row.className = 'input-group mb-2 option-row';
        row.dataset.index = count;
        row.innerHTML = `
            <span class="input-group-text bg-light fw-bold letter-badge">${letter}</span>
            <div class="input-group-text bg-white">
                <input class="form-check-input choice-selector mt-0" type="${isMultiple ? 'checkbox' : 'radio'}" name="correct_options[]" value="${count}" id="choice_check_${count}">
            </div>
            <input type="text" name="options[]" class="form-control" placeholder="Option ${letter} text..." required>
            <button type="button" class="btn btn-outline-danger" onclick="removeOptionRow(this)" title="Remove option">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        list.appendChild(row);
        reindexOptions();
    }

    function removeOptionRow(btn) {
        const list = document.getElementById('options-list');
        if (list.querySelectorAll('.option-row').length <= 2) {
            alert('A multiple choice question must have at least 2 choices.');
            return;
        }
        btn.closest('.option-row').remove();
        reindexOptions();
    }
</script>
@endsection
