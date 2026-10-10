@extends('layouts.exam')

@section('title', 'Exam Session - ' . $exam->title)
@section('exam-title', $exam->title)

@section('content')
<div class="container-fluid h-100">
    <div class="row h-100 g-3">
        <!-- LEFT: QUESTION DISPLAY AREA -->
        <div class="col-lg-8 col-xl-9 d-flex flex-column h-100">
            <div class="card border-0 shadow-sm rounded-3 flex-grow-1 d-flex flex-column overflow-hidden">
                <div class="card-body p-4 p-md-5 overflow-auto flex-grow-1">
                    @foreach ($questions as $index => $q)
                        @php
                            $ans = $answers->get($q->id);
                            $selectedOptIds = is_array($ans?->selected_option_ids) ? $ans->selected_option_ids : [];
                            $isFlagged = (bool) ($ans?->is_marked_for_review ?? false);
                            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                        @endphp

                        <div class="question-block {{ $index === 0 ? '' : 'd-none' }}" 
                             id="question-block-{{ $q->id }}" 
                             data-question-id="{{ $q->id }}"
                             data-index="{{ $index }}"
                             data-flagged="{{ $isFlagged ? 'true' : 'false' }}">

                            <!-- Question Header Meta -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill">
                                        Question {{ $index + 1 }} of {{ $questions->count() }}
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        {{ ucwords(str_replace('_', ' ', $q->question_type)) }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                        +{{ $q->points }} Marks
                                    </span>
                                    @if ($exam->negative_marking)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">
                                            -{{ $q->negative_points > 0 ? $q->negative_points : $exam->negative_mark_rate }} Penalty
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Question Prompt Text -->
                            <div class="fs-5 fw-semibold text-dark mb-4 lh-base">
                                {!! nl2br(e($q->question_text)) !!}
                            </div>

                            <!-- Options Form Input Area -->
                            <div class="options-group">
                                @if ($q->question_type === 'single_choice')
                                    @foreach ($q->options as $optIdx => $opt)
                                        @php
                                            $isChecked = in_array((int)$opt->id, array_map('intval', $selectedOptIds), true);
                                        @endphp
                                        <label class="question-option-card {{ $isChecked ? 'selected' : '' }}" for="opt_{{ $opt->id }}">
                                            <input type="radio" 
                                                   class="form-check-input answer-input d-none" 
                                                   name="q_{{ $q->id }}" 
                                                   id="opt_{{ $opt->id }}" 
                                                   value="{{ $opt->id }}" 
                                                   data-question-id="{{ $q->id }}"
                                                   {{ $isChecked ? 'checked' : '' }}
                                                   onchange="highlightOptionCard(this)">
                                            <span class="badge bg-light text-secondary border fw-bold px-2 py-1 fs-7">
                                                {{ $letters[$optIdx] ?? ($optIdx + 1) }}
                                            </span>
                                            <span class="fs-6 text-dark flex-grow-1">{{ $opt->option_text }}</span>
                                        </label>
                                    @endforeach

                                @elseif ($q->question_type === 'multiple_choice')
                                    <div class="alert alert-info py-2 small mb-3">
                                        <i class="bi bi-info-circle me-1"></i> Multiple answers may apply. Select all correct choices.
                                    </div>
                                    @foreach ($q->options as $optIdx => $opt)
                                        @php
                                            $isChecked = in_array((int)$opt->id, array_map('intval', $selectedOptIds), true);
                                        @endphp
                                        <label class="question-option-card {{ $isChecked ? 'selected' : '' }}" for="opt_{{ $opt->id }}">
                                            <input type="checkbox" 
                                                   class="form-check-input answer-input" 
                                                   name="q_{{ $q->id }}[]" 
                                                   id="opt_{{ $opt->id }}" 
                                                   value="{{ $opt->id }}" 
                                                   data-question-id="{{ $q->id }}"
                                                   {{ $isChecked ? 'checked' : '' }}
                                                   onchange="highlightOptionCard(this)">
                                            <span class="badge bg-light text-secondary border fw-bold px-2 py-1 fs-7">
                                                {{ $letters[$optIdx] ?? ($optIdx + 1) }}
                                            </span>
                                            <span class="fs-6 text-dark flex-grow-1">{{ $opt->option_text }}</span>
                                        </label>
                                    @endforeach

                                @elseif ($q->question_type === 'true_false')
                                    @foreach ($q->options as $opt)
                                        @php
                                            $isChecked = in_array((int)$opt->id, array_map('intval', $selectedOptIds), true);
                                            $isTrue = strtolower($opt->option_text) === 'true';
                                        @endphp
                                        <label class="question-option-card {{ $isChecked ? 'selected' : '' }} py-3" for="opt_{{ $opt->id }}">
                                            <input type="radio" 
                                                   class="form-check-input answer-input d-none" 
                                                   name="q_{{ $q->id }}" 
                                                   id="opt_{{ $opt->id }}" 
                                                   value="{{ $opt->id }}" 
                                                   data-question-id="{{ $q->id }}"
                                                   {{ $isChecked ? 'checked' : '' }}
                                                   onchange="highlightOptionCard(this)">
                                            <i class="bi bi-{{ $isTrue ? 'check-circle text-success' : 'x-circle text-danger' }} fs-4 me-2"></i>
                                            <span class="fs-5 fw-bold text-dark flex-grow-1">{{ $opt->option_text }}</span>
                                        </label>
                                    @endforeach

                                @elseif ($q->question_type === 'short_answer')
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Type Your Response Below:</label>
                                        <textarea class="form-control text-answer-input p-3" 
                                                  rows="3" 
                                                  data-question-id="{{ $q->id }}"
                                                  placeholder="Type exact answer here...">{{ $ans?->text_answer }}</textarea>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer Navigation Buttons -->
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex gap-2">
                        <button type="button" id="btn-prev" class="btn btn-outline-secondary px-3" disabled>
                            <i class="bi bi-chevron-left me-1"></i> Previous
                        </button>
                        <button type="button" id="btn-next" class="btn btn-primary px-3">
                            Next <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="btn-toggle-review" class="btn btn-outline-warning">
                            <i class="bi bi-flag-fill me-1"></i> Mark for Review
                        </button>
                        <button type="button" id="btn-clear-response" class="btn btn-outline-danger">
                            <i class="bi bi-eraser me-1"></i> Clear Response
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: QUESTION NAVIGATION PALETTE -->
        <div class="col-lg-4 col-xl-3 d-flex flex-column h-100">
            <div class="card border-0 shadow-sm rounded-3 flex-grow-1 d-flex flex-column overflow-hidden">
                <div class="card-header bg-white py-3 fw-bold border-bottom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i> Question Palette</span>
                    <span id="answered-counter" class="badge bg-primary text-white">
                        {{ $attempt->answered_questions }} / {{ $questions->count() }}
                    </span>
                </div>

                <!-- Legend -->
                <div class="p-3 bg-light border-bottom small">
                    <div class="row g-2 text-muted" style="font-size: 0.75rem;">
                        <div class="col-6 d-flex align-items-center gap-1">
                            <span class="d-inline-block rounded-circle bg-success" style="width: 10px; height: 10px;"></span> Answered
                        </div>
                        <div class="col-6 d-flex align-items-center gap-1">
                            <span class="d-inline-block rounded-circle bg-warning" style="width: 10px; height: 10px;"></span> Flagged
                        </div>
                        <div class="col-6 d-flex align-items-center gap-1">
                            <span class="d-inline-block rounded-circle bg-white border border-secondary" style="width: 10px; height: 10px;"></span> Unanswered
                        </div>
                        <div class="col-6 d-flex align-items-center gap-1">
                            <span class="d-inline-block rounded-circle border border-primary border-2" style="width: 10px; height: 10px;"></span> Current
                        </div>
                    </div>
                </div>

                <!-- Grid of Buttons -->
                <div class="card-body p-3 overflow-auto flex-grow-1">
                    <div class="palette-grid">
                        @foreach ($questions as $idx => $q)
                            <button type="button" 
                                    class="palette-btn" 
                                    id="palette-btn-{{ $q->id }}" 
                                    data-question-id="{{ $q->id }}" 
                                    data-index="{{ $idx }}">
                                {{ $idx + 1 }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Submit Button Area -->
                <div class="card-footer bg-white border-top p-3 text-center">
                    <button type="button" class="btn btn-success w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#confirmSubmitModal">
                        <i class="bi bi-check2-circle me-1"></i> Finish & Submit Exam
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUBMISSION CONFIRMATION MODAL -->
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="confirmSubmitModalLabel">
                    <i class="bi bi-exclamation-circle-fill text-primary me-2"></i> Confirm Examination Submission
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-question-circle text-warning fs-1 mb-2 d-block"></i>
                <h5 class="fw-bold mb-2">Are you sure you want to finish and submit?</h5>
                <p class="text-muted small mb-4">Once submitted, you will not be able to change or review any of your responses.</p>

                <div class="card bg-light border p-3 rounded-3 text-start mb-3">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Total Questions:</span>
                        <strong class="text-dark">{{ $questions->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Answered:</span>
                        <strong class="text-success" id="modal-answered-count">{{ $attempt->answered_questions }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Remaining / Unanswered:</span>
                        <strong class="text-danger" id="modal-unanswered-count">{{ $questions->count() - $attempt->answered_questions }}</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Resume Test</button>
                <form id="final-submit-form" action="{{ route('candidate.exam.submit', $attempt) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success px-4 fw-bold">Yes, Finalize & Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function highlightOptionCard(input) {
        if (input.type === 'radio') {
            const container = input.closest('.options-group');
            container.querySelectorAll('.question-option-card').forEach(card => card.classList.remove('selected'));
            input.closest('.question-option-card').classList.add('selected');
        } else if (input.type === 'checkbox') {
            if (input.checked) {
                input.closest('.question-option-card').classList.add('selected');
            } else {
                input.closest('.question-option-card').classList.remove('selected');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Instantiate the CAT testing engine
        window.catEngine = new CatExamEngine({
            attemptId: {{ $attempt->id }},
            remainingSeconds: {{ $remainingSeconds }},
            autosaveUrl: "{{ route('candidate.exam.autosave', $attempt) }}",
            violationUrl: "{{ route('candidate.exam.violation', $attempt) }}",
            submitUrl: "{{ route('candidate.exam.submit', $attempt) }}",
            csrfToken: "{{ csrf_token() }}",
            enforceFullscreen: {{ $exam->enforce_fullscreen ? 'true' : 'false' }},
            maxViolations: {{ $exam->max_violations }},
            totalQuestions: {{ $questions->count() }},
            questionsOrder: {!! json_encode($questions->pluck('id')) !!},
            initialViolations: {{ $attempt->violation_count }},
        });
    });
</script>
@endpush
