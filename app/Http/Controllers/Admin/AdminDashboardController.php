<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_exams'      => Exam::count(),
            'active_exams'     => Exam::where('is_active', true)->where('status', 'published')->count(),
            'total_questions'  => Question::count(),
            'total_candidates' => User::where('role', 'candidate')->count(),
            'total_attempts'   => ExamAttempt::count(),
            'active_sessions'  => ExamAttempt::where('status', 'in_progress')->count(),
            'passed_attempts'  => ExamAttempt::where('is_passed', true)->count(),
            'average_score'    => round(ExamAttempt::where('status', 'completed')->avg('score') ?? 0, 1),
        ];

        // Overall pass rate
        $completedAttempts = ExamAttempt::whereIn('status', ['completed', 'timed_out', 'terminated_violation'])->count();
        $stats['pass_rate'] = $completedAttempts > 0 
            ? round(($stats['passed_attempts'] / $completedAttempts) * 100, 1) 
            : 0;

        // Recent completed or in-progress attempts
        $recentAttempts = ExamAttempt::with(['exam', 'user'])
            ->latest('started_at')
            ->take(10)
            ->get();

        // Exams overview
        $exams = Exam::withCount(['questions', 'attempts'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentAttempts', 'exams'));
    }
}
