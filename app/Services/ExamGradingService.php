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
            $exam = $attempt->exam;
            $attempt->load(['answers.question.options']);

            $totalQuestions = $attempt->answers->count();
            $answeredCount = 0;
            $correctCount = 0;
            $incorrectCount = 0;
            $totalEarnedPoints = 0.00;
            $possibleTotalPoints = 0.00;

            foreach ($attempt->answers as $answer) {
                $question = $answer->question;
                $possibleTotalPoints += (float) $question->points;

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
                            }
                        }
                        break;

                    case 'short_answer':
                        $text = trim((string) $answer->text_answer);
                        if ($text !== '') {
                            $hasAnswered = true;
                            // Check against correct options or explanation keywords
                            $correctAnswers = $question->options->pluck('option_text')->map(fn($t) => strtolower(trim($t)))->all();
                            if (in_array(strtolower($text), $correctAnswers, true)) {
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
                        // Apply negative marking if configured on exam or question
                        if ($exam->negative_marking) {
                            $penalty = (float) ($question->negative_points > 0 ? $question->negative_points : ($exam->negative_mark_rate ?? 0));
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

            $attempt->update([
                'status'              => $finalStatus,
                'submitted_at'        => $attempt->submitted_at ?? Carbon::now(),
                'total_questions'     => $totalQuestions,
                'answered_questions'  => $answeredCount,
                'correct_answers'     => $correctCount,
                'incorrect_answers'   => $incorrectCount,
                'score'               => $totalEarnedPoints,
                'percentage'          => $percentage,
                'is_passed'           => $isPassed,
            ]);

            return $attempt->fresh();
        });
    }
}
