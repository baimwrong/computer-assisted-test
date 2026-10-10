@extends('layouts.app')

@section('title', 'Exam Review - ' . $exam->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-card-checklist text-primary me-2"></i> Post-Exam Solution Review</h3>
        <p class="text-muted mb-0">Exam: <strong>{{ $exam->title }}</strong> | Final Score: <strong>{{ $attempt->score }} ({{ $attempt->percentage }}%)</strong></p>
    </div>
    <a href="{{ route('candidate.exam.result', $attempt) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Scorecard
    </a>
</div>

<div class="row">
    <div class="col-lg-9 mx-auto">
        @foreach ($attempt->answers as $idx => $ans)
            @php
                $q = $ans->question;
                $userSelected = is_array($ans->selected_option_ids) ? array_map('intval', $ans->selected_option_ids) : [];
            @endphp
            <div class="card border-0 shadow-sm rounded-3 mb-4 {{ $ans->is_correct ? 'border-start border-success border-4' : ($ans->is_correct === false ? 'border-start border-danger border-4' : 'border-start border-secondary border-4') }}">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Question {{ $idx + 1 }}</span>
                    <div>
                        @if ($ans->is_correct)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Correct (+{{ $ans->points_awarded }} pts)</span>
                        @elseif ($ans->is_correct === false)
                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Incorrect ({{ $ans->points_awarded }} pts)</span>
                        @else
                            <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i> Not Answered (0 pts)</span>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <p class="fw-semibold fs-6 mb-3 text-dark">{!! nl2br(e($q->question_text)) !!}</p>

                    <!-- Options list for choice questions -->
                    @if (in_array($q->question_type, ['single_choice', 'multiple_choice', 'true_false']))
                        <div class="list-group mb-3">
                            @foreach ($q->options as $opt)
                                @php
                                    $isSelected = in_array((int)$opt->id, $userSelected, true);
                                    $isCorrectOpt = (bool) $opt->is_correct;
                                @endphp
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 {{ $isCorrectOpt ? 'bg-success bg-opacity-10 border-success' : ($isSelected && !$isCorrectOpt ? 'bg-danger bg-opacity-10 border-danger' : '') }}">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($isSelected && $isCorrectOpt)
                                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                        @elseif ($isSelected && !$isCorrectOpt)
                                            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                        @elseif ($isCorrectOpt)
                                            <i class="bi bi-check-circle text-success fs-5"></i>
                                        @else
                                            <i class="bi bi-circle text-muted fs-5"></i>
                                        @endif
                                        <span class="{{ $isCorrectOpt ? 'fw-bold text-success' : ($isSelected ? 'fw-bold text-danger' : 'text-dark') }}">
                                            {{ $opt->option_text }}
                                        </span>
                                    </div>
                                    <div>
                                        @if ($isSelected)
                                            <span class="badge bg-primary fs-8">Your Answer</span>
                                        @endif
                                        @if ($isCorrectOpt)
                                            <span class="badge bg-success fs-8">Correct Answer</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($q->question_type === 'short_answer')
                        <div class="mb-3 p-3 bg-light rounded-3 border">
                            <div class="mb-1">
                                <span class="text-muted small">Your Submitted Text:</span>
                                <strong>{{ $ans->text_answer ?: '(No response entered)' }}</strong>
                            </div>
                            <div>
                                <span class="text-muted small">Accepted Answer(s):</span>
                                <strong class="text-success">{{ $q->options->pluck('option_text')->implode(' OR ') }}</strong>
                            </div>
                        </div>
                    @endif

                    <!-- Explanation Box -->
                    @if ($q->explanation)
                        <div class="bg-light p-3 rounded-3 border border-info border-start border-3 small">
                            <strong class="text-primary d-block mb-1"><i class="bi bi-lightbulb-fill me-1"></i> Rationale & Explanation:</strong>
                            <p class="mb-0 text-secondary">{{ $q->explanation }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
