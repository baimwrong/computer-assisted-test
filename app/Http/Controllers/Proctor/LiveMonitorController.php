<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\AuditLogService;
use App\Services\ExamGradingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LiveMonitorController extends Controller
{
    public function __construct(
        protected ExamGradingService $gradingService
    ) {}

    public function index(Request $request)
    {
        $exams = Exam::where('is_active', true)->orderBy('title')->get();
        $selectedExamId = $request->query('exam_id');

        $query = ExamAttempt::with(['exam', 'user', 'violations'])
            ->where('status', 'in_progress');

        if ($selectedExamId) {
            $query->where('exam_id', $selectedExamId);
        }

        $activeAttempts = $query->latest('started_at')->get();

        return view('proctor.monitor', compact('exams', 'activeAttempts', 'selectedExamId'));
    }

    /**
     * JSON API for live proctor dashboard polling.
     */
    public function apiSessions(Request $request): JsonResponse
    {
        $query = ExamAttempt::with(['exam', 'user', 'violations'])
            ->where('status', 'in_progress');

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        $attempts = $query->latest('started_at')->get()->map(function ($att) {
            return [
                'id'                 => $att->id,
                'candidate_name'     => $att->user->name,
                'student_id'         => $att->user->student_id ?? 'N/A',
                'exam_title'         => $att->exam->title,
                'answered_questions' => $att->answered_questions,
                'total_questions'    => $att->total_questions,
                'remaining_seconds'  => $att->remainingSeconds(),
                'status'             => $att->status,
                'connection_status'  => $att->connectionStatus(),
                'last_seen_at'       => $att->last_seen_at ? $att->last_seen_at->toTimeString() : null,
                'violation_count'    => $att->violation_count,
                'ip_address'         => $att->ip_address,
                'started_at'         => $att->started_at->toTimeString(),
            ];
        });

        return response()->json(['attempts' => $attempts]);
    }

    public function grantExtraTime(Request $request, ExamAttempt $attempt)
    {
        $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        if ($attempt->status !== 'in_progress') {
            return back()->with('error', 'Attempt is not currently in progress.');
        }

        $minutes = (int) $request->minutes;
        $attempt->expires_at = $attempt->expires_at->addMinutes($minutes);
        $attempt->save();

        AuditLogService::log('proctor_grant_extra_time', 'ExamAttempt', $attempt->id, [
            'minutes_added' => $minutes,
            'new_expiry'    => $attempt->expires_at->toIso8601String(),
            'candidate'     => $attempt->user->name,
        ]);

        return back()->with('success', "Granted {$minutes} extra minutes to {$attempt->user->name}.");
    }

    public function forceSubmit(ExamAttempt $attempt)
    {
        if ($attempt->status !== 'in_progress') {
            return back()->with('error', 'Attempt is already finished.');
        }

        $this->gradingService->gradeAttempt($attempt, 'completed');

        AuditLogService::log('proctor_force_submit', 'ExamAttempt', $attempt->id, [
            'candidate' => $attempt->user->name,
        ]);

        return back()->with('success', "Attempt for {$attempt->user->name} was forcefully submitted and graded.");
    }

    public function terminate(ExamAttempt $attempt)
    {
        if ($attempt->status !== 'in_progress') {
            return back()->with('error', 'Attempt is already finished.');
        }

        $this->gradingService->gradeAttempt($attempt, 'terminated_violation');

        AuditLogService::log('proctor_terminate_attempt', 'ExamAttempt', $attempt->id, [
            'candidate' => $attempt->user->name,
        ]);

        return back()->with('success', "Attempt for {$attempt->user->name} was terminated due to security violation.");
    }

    public function resetViolations(ExamAttempt $attempt)
    {
        $previous = $attempt->violation_count;
        $attempt->violation_count = 0;
        $attempt->save();

        AuditLogService::log('proctor_reset_violations', 'ExamAttempt', $attempt->id, [
            'previous_violations' => $previous,
            'candidate'           => $attempt->user->name,
        ]);

        return back()->with('success', "Security violations reset to 0 for {$attempt->user->name}.");
    }

    public function resumeSession(Request $request, ExamAttempt $attempt)
    {
        $extraMinutes = (int) $request->input('minutes', 10);
        $now = Carbon::now();

        $newExpiry = ($attempt->expires_at && $attempt->expires_at->gt($now))
            ? $attempt->expires_at->copy()->addMinutes($extraMinutes)
            : $now->copy()->addMinutes($extraMinutes);

        $attempt->update([
            'status'          => 'in_progress',
            'submitted_at'    => null,
            'expires_at'      => $newExpiry,
            'violation_count' => 0,
            'last_seen_at'    => $now,
        ]);

        AuditLogService::log('proctor_resume_session', 'ExamAttempt', $attempt->id, [
            'new_expiry' => $newExpiry->toIso8601String(),
            'candidate'  => $attempt->user->name,
        ]);

        return back()->with('success', "Session for {$attempt->user->name} has been resumed with {$extraMinutes} minutes remaining.");
    }
}
