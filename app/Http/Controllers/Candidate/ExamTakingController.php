<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\ExamEngineService;
use App\Services\ExamGradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ExamTakingController extends Controller
{
    public function __construct(
        protected ExamEngineService $engineService,
        protected ExamGradingService $gradingService
    ) {}

    /**
     * Pre-exam lobby and verification screen.
     */
    public function lobby(Exam $exam)
    {
        $user = Auth::user();

        // Check if candidate already has an in-progress attempt for this exam
        $activeAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeAttempt) {
            return redirect()->route('candidate.exam.take', $activeAttempt);
        }

        // Verify scheduled availability window for new attempts
        if (!$exam->isAvailable()) {
            if ($exam->isUpcoming()) {
                return redirect()->route('candidate.dashboard')
                    ->with('warning', "Exam '{$exam->title}' has not opened yet. Scheduled to begin at {$exam->start_time->format('M d, Y H:i T')}.");
            }
            if ($exam->isExpiredSchedule()) {
                return redirect()->route('candidate.dashboard')
                    ->with('warning', "Exam '{$exam->title}' has closed. It concluded at {$exam->end_time->format('M d, Y H:i T')}.");
            }
            return redirect()->route('candidate.dashboard')
                ->with('error', "Exam '{$exam->title}' is currently unavailable.");
        }

        // Enforce maximum attempts limit (default 1 attempt)
        $completedAttemptsCount = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'timed_out', 'terminated_violation'])
            ->count();

        $maxAttempts = $exam->max_attempts ?? 1;
        if ($completedAttemptsCount >= $maxAttempts) {
            $lastAttempt = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($lastAttempt) {
                return redirect()->route('candidate.exam.result', $lastAttempt)
                    ->with('info', "You have already completed the maximum permitted attempts ({$maxAttempts}) for this examination.");
            }
        }

        $exam->load('category');
        $questionCount = $exam->questions()->where('is_active', true)->count();

        return view('candidate.exam.lobby', compact('exam', 'questionCount'));
    }

    /**
     * Start the examination session.
     */
    public function start(Request $request, Exam $exam)
    {
        $user = Auth::user();

        if (!$exam->isAvailable()) {
            return redirect()->route('candidate.dashboard')
                ->with('error', "Exam '{$exam->title}' is not open for new attempts.");
        }

        // Enforce maximum attempts limit before starting new attempt
        $completedAttemptsCount = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'timed_out', 'terminated_violation'])
            ->count();

        $maxAttempts = $exam->max_attempts ?? 1;
        if ($completedAttemptsCount >= $maxAttempts) {
            return redirect()->route('candidate.dashboard')
                ->with('error', "Maximum attempt limit ({$maxAttempts}) reached for this examination.");
        }

        try {
            $attempt = $this->engineService->startAttempt(
                $exam,
                $user,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->route('candidate.exam.take', $attempt);
        } catch (\Exception $e) {
            return redirect()->route('candidate.dashboard')->with('error', $e->getMessage());
        }
    }

    /**
     * Main Exam Taking Interface.
     */
    public function take(ExamAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('candidate.exam.result', $attempt);
        }

        if ($attempt->isExpired()) {
            $graded = $this->gradingService->gradeAttempt($attempt, 'timed_out');
            return redirect()->route('candidate.exam.result', $graded)
                ->with('warning', 'Exam time elapsed. Your answers were automatically submitted.');
        }

        $exam = $attempt->exam;
        
        // Retrieve questions in the randomized or assigned sequence
        $order = $attempt->metadata['question_order'] ?? [];
        $questions = Question::with('options')
            ->whereIn('id', $order)
            ->get()
            ->sortBy(function ($q) use ($order) {
                return array_search($q->id, $order);
            })
            ->values();

        // Apply attempt's deterministic option order if shuffled
        $optionsOrder = $attempt->metadata['options_order'] ?? [];
        if (!empty($optionsOrder)) {
            foreach ($questions as $q) {
                if (isset($optionsOrder[$q->id])) {
                    $optOrder = $optionsOrder[$q->id];
                    $sortedOptions = $q->options->sortBy(fn($o) => array_search($o->id, $optOrder))->values();
                    $q->setRelation('options', $sortedOptions);
                }
            }
        }

        // Load existing answers keyed by question_id
        $answers = $attempt->answers->keyBy('question_id');

        $remainingSeconds = $attempt->remainingSeconds();

        return view('candidate.exam.take', compact('attempt', 'exam', 'questions', 'answers', 'remainingSeconds'));
    }

    /**
     * Autosave response for a question via AJAX.
     */
    public function autosave(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        $validated = $request->validate([
            'question_id'          => ['required', 'integer'],
            'selected_option_ids'  => ['nullable', 'array'],
            'text_answer'          => ['nullable', 'string'],
            'is_marked_for_review' => ['nullable', 'boolean'],
        ]);

        try {
            if ($attempt->status === 'in_progress') {
                $attempt->update(['last_seen_at' => \Carbon\Carbon::now()]);
            }

            $answer = $this->engineService->autosaveAnswer(
                $attempt,
                $validated['question_id'],
                $validated
            );

            return response()->json([
                'success'            => true,
                'answered_questions' => $attempt->answered_questions,
                'total_questions'    => $attempt->total_questions,
                'remaining_seconds'  => $attempt->remainingSeconds(),
                'answer'             => $answer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Anti-cheat violation report via AJAX.
     */
    public function logViolation(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        $validated = $request->validate([
            'violation_type' => ['required', 'string'],
            'details'        => ['nullable', 'string'],
        ]);

        $result = $this->engineService->recordViolation(
            $attempt,
            $validated['violation_type'],
            $validated['details'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Periodic candidate heartbeat for session keep-alive, timer sync, and live proctor updates.
     */
    public function heartbeat(ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->status === 'in_progress') {
            $attempt->update(['last_seen_at' => \Carbon\Carbon::now()]);
        }

        return response()->json([
            'status'            => $attempt->status,
            'remaining_seconds' => $attempt->remainingSeconds(),
            'answered_count'    => $attempt->answered_questions,
            'total_questions'   => $attempt->total_questions,
            'violations'        => $attempt->violation_count,
            'result_url'        => route('candidate.exam.result', $attempt),
        ]);
    }

    /**
     * Submit exam attempt.
     */
    public function submit(Request $request, ExamAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('candidate.exam.result', $attempt);
        }

        $graded = $this->engineService->submitAttempt($attempt);

        return redirect()->route('candidate.exam.result', $graded)
            ->with('success', 'Your exam has been successfully submitted and evaluated.');
    }

    /**
     * Scorecard and Result screen.
     */
    public function result(ExamAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if (!$attempt->isCompleted()) {
            return redirect()->route('candidate.exam.take', $attempt)
                ->with('info', 'Your examination session is still in progress.');
        }

        $attempt->load(['exam.category', 'answers.question']);

        return view('candidate.exam.result', compact('attempt'));
    }

    /**
     * Question-by-question review screen.
     */
    public function review(ExamAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if (!$attempt->isCompleted()) {
            return redirect()->route('candidate.exam.take', $attempt)
                ->with('warning', 'Detailed question review is only available after your examination is completed.');
        }

        $exam = $attempt->exam;

        if (!$exam->allow_review) {
            abort(403, 'Detailed review is disabled for this examination.');
        }

        $attempt->load(['answers.question.options']);

        return view('candidate.exam.review', compact('attempt', 'exam'));
    }

    protected function authorizeAttempt(ExamAttempt $attempt): void
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this examination session.');
        }
    }
}
