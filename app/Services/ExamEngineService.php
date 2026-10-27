<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use App\Models\ExamViolation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class ExamEngineService
{
    public function __construct(
        protected ExamGradingService $gradingService
    ) {}

    /**
     * Start or resume an active exam attempt for a candidate.
     */
    public function startAttempt(Exam $exam, User $user, string $ipAddress, ?string $userAgent = null): ExamAttempt
    {
        return DB::transaction(function () use ($exam, $user, $ipAddress, $userAgent) {
            // Check for active attempt with pessimistic lock to prevent double-start race condition
            $existing = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->isExpired()) {
                    return $this->gradingService->gradeAttempt($existing, 'timed_out');
                }
                return $existing;
            }

            // Verify exam availability
            if (!$exam->isAvailable()) {
                throw new Exception('This examination is currently not active or available.');
            }

            $questionsQuery = $exam->questions()->with('options')->where('is_active', true);
            $questions = $questionsQuery->get();

            if ($questions->isEmpty()) {
                throw new Exception('This exam currently has no active questions.');
            }

            if ($exam->shuffle_questions) {
                $questions = $questions->shuffle();
            }

            $questionOrder = $questions->pluck('id')->all();

            // Deterministic option shuffling per attempt
            $optionsOrder = [];
            if ($exam->shuffle_options) {
                foreach ($questions as $q) {
                    if ($q->options->isNotEmpty()) {
                        $optionsOrder[$q->id] = $q->options->pluck('id')->shuffle()->values()->all();
                    }
                }
            }

            $attemptCount = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->count();

            $startedAt = Carbon::now();
            $expiresAt = $startedAt->copy()->addMinutes($exam->duration_minutes);

            // Cap expires_at to exam end_time if scheduled
            if ($exam->end_time && $expiresAt->gt($exam->end_time)) {
                $expiresAt = $exam->end_time->copy();
            }

            $attempt = ExamAttempt::create([
                'exam_id'            => $exam->id,
                'user_id'            => $user->id,
                'attempt_number'     => $attemptCount + 1,
                'status'             => 'in_progress',
                'started_at'         => $startedAt,
                'expires_at'         => $expiresAt,
                'total_questions'    => $questions->count(),
                'answered_questions' => 0,
                'violation_count'    => 0,
                'ip_address'         => $ipAddress,
                'user_agent'         => $userAgent,
                'metadata'           => [
                    'question_order'  => $questionOrder,
                    'shuffle_options' => $exam->shuffle_options,
                    'options_order'   => $optionsOrder,
                ],
            ]);

            // High-concurrency bulk insertion for attempt answers
            $nowTimestamp = Carbon::now();
            $answersToInsert = [];
            foreach ($questions as $question) {
                $answersToInsert[] = [
                    'attempt_id'           => $attempt->id,
                    'question_id'          => $question->id,
                    'selected_option_ids'  => null,
                    'text_answer'          => null,
                    'is_correct'           => null,
                    'points_awarded'       => 0.00,
                    'is_marked_for_review' => false,
                    'answered_at'          => null,
                    'created_at'           => $nowTimestamp,
                    'updated_at'           => $nowTimestamp,
                ];
            }
            AttemptAnswer::insert($answersToInsert);

            AuditLogService::log('exam_started', 'ExamAttempt', $attempt->id, [
                'exam_title' => $exam->title,
                'candidate'  => $user->name,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return $attempt;
        });
    }

    /**
     * Autosave response for a specific question.
     */
    public function autosaveAnswer(ExamAttempt $attempt, int $questionId, array $data): AttemptAnswer
    {
        if ($attempt->status !== 'in_progress') {
            throw new Exception('Exam session is already concluded.');
        }

        if ($attempt->isExpired()) {
            $this->gradingService->gradeAttempt($attempt, 'timed_out');
            throw new Exception('Exam duration has expired. Test has been automatically submitted.');
        }

        $answer = AttemptAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->first();

        if (!$answer) {
            $answer = AttemptAnswer::create([
                'attempt_id'           => $attempt->id,
                'question_id'          => $questionId,
                'selected_option_ids'  => null,
                'text_answer'          => null,
                'is_marked_for_review' => false,
            ]);
        }

        $updateData = ['answered_at' => Carbon::now()];

        if (array_key_exists('selected_option_ids', $data)) {
            $selected = $data['selected_option_ids'];
            $filtered = is_array($selected) ? array_values(array_filter($selected)) : null;
            $updateData['selected_option_ids'] = !empty($filtered) ? $filtered : null;
        }

        if (array_key_exists('text_answer', $data)) {
            $text = is_string($data['text_answer']) ? trim($data['text_answer']) : null;
            $updateData['text_answer'] = ($text !== '' && $text !== null) ? $text : null;
        }

        if (array_key_exists('is_marked_for_review', $data)) {
            $updateData['is_marked_for_review'] = (bool) $data['is_marked_for_review'];
        }

        $answer->update($updateData);

        // Recalculate answered count accurately (ignoring empty strings)
        $answeredCount = AttemptAnswer::where('attempt_id', $attempt->id)
            ->where(function ($query) {
                $query->whereNotNull('selected_option_ids')
                      ->orWhere(function ($q) {
                          $q->whereNotNull('text_answer')
                            ->where('text_answer', '!=', '');
                      });
            })
            ->count();

        $attempt->update(['answered_questions' => $answeredCount]);

        return $answer->fresh();
    }

    /**
     * Record security or proctoring violation.
     */
    public function recordViolation(ExamAttempt $attempt, string $violationType, ?string $details = null): array
    {
        if ($attempt->status !== 'in_progress') {
            return ['status' => 'inactive', 'attempt' => $attempt];
        }

        $attempt->increment('violation_count');
        $attempt->refresh();

        ExamViolation::create([
            'attempt_id'     => $attempt->id,
            'user_id'        => $attempt->user_id,
            'violation_type' => $violationType,
            'details'        => $details,
            'recorded_at'    => Carbon::now(),
        ]);

        $maxViolations = $attempt->exam->max_violations ?? 3;
        $autoSubmit = $attempt->exam->auto_submit_on_violation ?? true;

        if ($autoSubmit && $attempt->violation_count >= $maxViolations) {
            $this->gradingService->gradeAttempt($attempt, 'terminated_violation');
            
            AuditLogService::log('exam_terminated_violation', 'ExamAttempt', $attempt->id, [
                'violations' => $attempt->violation_count,
                'last_violation' => $violationType,
            ]);

            return [
                'status' => 'terminated',
                'message' => 'Maximum proctoring violations exceeded. Your test has been terminated.',
                'violation_count' => $attempt->violation_count,
                'max_violations' => $maxViolations,
            ];
        }

        return [
            'status' => 'warning',
            'message' => "Security warning recorded ({$attempt->violation_count} of {$maxViolations} permitted).",
            'violation_count' => $attempt->violation_count,
            'max_violations' => $maxViolations,
        ];
    }

    /**
     * Submit an exam by candidate.
     */
    public function submitAttempt(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->status !== 'in_progress') {
            return $attempt;
        }

        $graded = $this->gradingService->gradeAttempt($attempt, 'completed');

        AuditLogService::log('exam_submitted', 'ExamAttempt', $attempt->id, [
            'score' => $graded->score,
            'percentage' => $graded->percentage,
            'is_passed' => $graded->is_passed,
        ]);

        return $graded;
    }
}
