<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamViolation;
use App\Models\Question;
use App\Models\AttemptAnswer;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $exams = Exam::withCount(['attempts', 'questions'])
            ->with(['category'])
            ->get();

        // Pass vs Fail distribution
        $passedCount = ExamAttempt::where('is_passed', true)->count();
        $failedCount = ExamAttempt::where('status', 'completed')->where('is_passed', false)->count();

        // Score averages per exam (ANSI SQL compliant across PostgreSQL, MySQL, and SQLite)
        $examScores = Exam::select('exams.id', 'exams.title')
            ->join('exam_attempts', 'exams.id', '=', 'exam_attempts.exam_id')
            ->where('exam_attempts.status', 'completed')
            ->groupBy('exams.id', 'exams.title')
            ->selectRaw('ROUND(CAST(AVG(exam_attempts.score) AS decimal(8,2)), 2) as avg_score')
            ->selectRaw('ROUND(CAST(AVG(exam_attempts.percentage) AS decimal(8,2)), 1) as avg_pct')
            ->get();

        // Violation breakdown
        $violationsBreakdown = ExamViolation::select('violation_type', DB::raw('count(*) as count'))
            ->groupBy('violation_type')
            ->pluck('count', 'violation_type')
            ->all();

        return view('admin.analytics.index', compact('exams', 'passedCount', 'failedCount', 'examScores', 'violationsBreakdown'));
    }

    public function exam(Exam $exam)
    {
        $exam->load('category');

        $attempts = ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('status', ['completed', 'timed_out', 'terminated_violation'])
            ->get();

        $totalCompleted = $attempts->count();

        // Distribution buckets: 0-20, 21-40, 41-60, 61-80, 81-100
        $buckets = [
            '0-20%'   => $attempts->whereBetween('percentage', [0, 20])->count(),
            '21-40%'  => $attempts->whereBetween('percentage', [20.01, 40])->count(),
            '41-60%'  => $attempts->whereBetween('percentage', [40.01, 60])->count(),
            '61-80%'  => $attempts->whereBetween('percentage', [60.01, 80])->count(),
            '81-100%' => $attempts->whereBetween('percentage', [80.01, 100])->count(),
        ];

        // Item Analysis for questions
        $questions = Question::where('exam_id', $exam->id)
            ->with(['options'])
            ->get()
            ->map(function ($question) {
                $answers = AttemptAnswer::where('question_id', $question->id)
                    ->whereNotNull('answered_at')
                    ->get();

                $totalAnswered = $answers->count();
                $correctCount = $answers->where('is_correct', true)->count();
                $difficultyIndex = $totalAnswered > 0 ? round($correctCount / $totalAnswered, 2) : 0.00;

                // Difficulty assessment
                $eval = match (true) {
                    $totalAnswered === 0 => 'Not evaluated',
                    $difficultyIndex < 0.30 => 'Difficult',
                    $difficultyIndex > 0.80 => 'Easy',
                    default => 'Optimal',
                };

                return [
                    'id'               => $question->id,
                    'text'             => $question->question_text,
                    'type'             => $question->question_type,
                    'points'           => $question->points,
                    'total_answered'   => $totalAnswered,
                    'correct_count'    => $correctCount,
                    'difficulty_index' => $difficultyIndex,
                    'evaluation'       => $eval,
                ];
            });

        return view('admin.analytics.exam', compact('exam', 'totalCompleted', 'buckets', 'questions'));
    }
}
