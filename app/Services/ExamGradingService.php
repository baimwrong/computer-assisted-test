<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamGradingService
{
    /**
     * Grade an exam attempt and calculate final score and metrics.
     */
    public function gradeAttempt(ExamAttempt $attempt, string $finalStatus = 'completed'): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $finalStatus) {
            // Lock attempt row to prevent double-grading race condition
            $lockedAttempt = ExamAttempt::where('id', $attempt->id)->lockForUpdate()->first();
            if (!$lockedAttempt || $lockedAttempt->status !== 'in_progress') {
                return $lockedAttempt ?? $attempt;
            }

            $exam = $lockedAttempt->exam;
            $lockedAttempt->load(['answers.question.options']);

            $examQuestions = $exam->questions()->with('options')->where('is_active', true)->get();
            $answersByQuestionId = $lockedAttempt->answers->keyBy('question_id');

            $totalQuestions = $examQuestions->count();
            $answeredCount = 0;
            $correctCount = 0;
            $incorrectCount = 0;
            $totalEarnedPoints = 0.00;
            $possibleTotalPoints = 0.00;

            foreach ($examQuestions as $question) {
                $possibleTotalPoints += (float) $question->points;
                $answer = $answersByQuestionId->get($question->id);

                if (!$answer) {
                    $answer = AttemptAnswer::create([
                        'attempt_id'           => $lockedAttempt->id,
                        'question_id'          => $question->id,
                        'selected_option_ids'  => null,
                        'text_answer'          => null,
                        'is_marked_for_review' => false,
                    ]);
                }

                $hasAnswered = false;
                $isCorrect = false;
                $pointsAwarded = 0.00;

                switch ($question->question_type) {
                    case 'single_choice':
                    case 'true_false':
                        $selectedOptionIds = is_array($answer->selected_option_ids) ? $answer->selected_option_ids : [];
                        if (!empty($selectedOptionIds)) {
                            $hasAnswered = true;
                            $selectedId = (int) $selectedOptionIds[0];
                            $correctOption = $question->options->firstWhere('is_correct', true);
                            if ($correctOption && (int) $correctOption->id === $selectedId) {
                                $isCorrect = true;
                                $pointsAwarded = (float) $question->points;
                            }
                        }
                        break;

                    case 'multiple_choice':
                        $selectedOptionIds = is_array($answer->selected_option_ids) ? array_map('intval', $answer->selected_option_ids) : [];
                        if (!empty($selectedOptionIds)) {
                            $hasAnswered = true;
                            $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn($id) => (int) $id)->values()->all();
                            sort($selectedOptionIds);
                            sort($correctIds);

                            if ($selectedOptionIds === $correctIds) {
                                $isCorrect = true;
                                $pointsAwarded = (float) $question->points;
                            } else {
                                // Proportional partial credit if selected choices contain no wrong answers
                                $chosenCorrect = array_intersect($selectedOptionIds, $correctIds);
                                $chosenWrong = array_diff($selectedOptionIds, $correctIds);
                                if (!empty($chosenCorrect) && empty($chosenWrong) && count($correctIds) > 0) {
                                    $partialFraction = count($chosenCorrect) / count($correctIds);
                                    $pointsAwarded = round((float) $question->points * $partialFraction, 2);
                                    $isCorrect = true; // partial pass, exempt from negative penalty
                                }
                            }
                        }
                        break;

                    case 'short_answer':
                        $text = trim((string) $answer->text_answer);
                        if ($text !== '') {
                            $hasAnswered = true;
                            // Clean input: collapse duplicate spaces and strip surrounding punctuation
                            $normalizedInput = strtolower(preg_replace('/\s+/', ' ', trim($text, " \t\n\r\0\x0B.,;:!?")));
                            $correctAnswers = $question->options
                                ->where('is_correct', true)
                                ->pluck('option_text')
                                ->map(fn($t) => strtolower(preg_replace('/\s+/', ' ', trim($t, " \t\n\r\0\x0B.,;:!?"))))
                                ->all();

                            if (in_array($normalizedInput, $correctAnswers, true)) {
                                $isCorrect = true;
                                $pointsAwarded = (float) $question->points;
                            }
                        }
                        break;
                }

                if ($hasAnswered) {
                    $answeredCount++;
                    if ($isCorrect) {
                        $correctCount++;
                    } else {
                        $incorrectCount++;
                        // Apply negative marking: question penalty overrides exam rate if specified
                        if ($exam->negative_marking) {
                            $penalty = ($question->negative_points !== null && (float) $question->negative_points >= 0)
                                ? (float) $question->negative_points
                                : (float) ($exam->negative_mark_rate ?? 0);
                            $pointsAwarded -= $penalty;
                        }
                    }
                }

                $answer->update([
                    'is_correct'     => $hasAnswered ? $isCorrect : null,
                    'points_awarded' => round($pointsAwarded, 2),
                ]);

                $totalEarnedPoints += $pointsAwarded;
            }

            // Ensure earned points do not go below zero
            $totalEarnedPoints = max(0.00, round($totalEarnedPoints, 2));

            // Calculate percentage
            $percentage = $possibleTotalPoints > 0 
                ? round(($totalEarnedPoints / $possibleTotalPoints) * 100, 2)
                : 0.00;

            $isPassed = $percentage >= (float) $exam->pass_percentage;

            $lockedAttempt->update([
                'status'              => $finalStatus,
                'submitted_at'        => $lockedAttempt->submitted_at ?? Carbon::now(),
                'total_questions'     => $totalQuestions,
                'answered_questions'  => $answeredCount,
                'correct_answers'     => $correctCount,
                'incorrect_answers'   => $incorrectCount,
                'score'               => $totalEarnedPoints,
                'percentage'          => $percentage,
                'is_passed'           => $isPassed,
            ]);

            return $lockedAttempt->fresh();
        });
    }
}
