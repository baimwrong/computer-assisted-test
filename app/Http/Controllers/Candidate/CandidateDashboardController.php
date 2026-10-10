<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Auth;

class CandidateDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Check for active ongoing attempt
        $activeAttempt = ExamAttempt::with('exam')
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        // Available exams
        $availableExams = Exam::with(['category'])
            ->withCount('questions')
            ->where('is_active', true)
            ->where('status', 'published')
            ->orderBy('title')
            ->get();

        // Recent completed attempts
        $recentAttempts = ExamAttempt::with('exam')
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'timed_out', 'terminated_violation'])
            ->latest('submitted_at')
            ->take(5)
            ->get();

        $stats = [
            'total_taken' => ExamAttempt::where('user_id', $user->id)->count(),
            'passed'      => ExamAttempt::where('user_id', $user->id)->where('is_passed', true)->count(),
            'failed'      => ExamAttempt::where('user_id', $user->id)->where('status', 'completed')->where('is_passed', false)->count(),
        ];

        return view('candidate.dashboard', compact('activeAttempt', 'availableExams', 'recentAttempts', 'stats'));
    }

    public function history()
    {
        $user = Auth::user();

        $attempts = ExamAttempt::with('exam.category')
            ->where('user_id', $user->id)
            ->latest('started_at')
            ->paginate(15);

        return view('candidate.exam.history', compact('attempts'));
    }
}
