<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $query = Exam::with(['category', 'creator'])
            ->withCount(['questions', 'attempts']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->latest()->paginate(15);
        $categories = Category::orderBy('name')->get();

        return view('admin.exams.index', compact('exams', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.exams.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'             => ['required', 'exists:categories,id'],
            'title'                   => ['required', 'string', 'max:255'],
            'code'                    => ['required', 'string', 'max:50', 'unique:exams,code'],
            'description'             => ['nullable', 'string'],
            'instructions'            => ['nullable', 'string'],
            'duration_minutes'        => ['required', 'integer', 'min:1', 'max:600'],
            'pass_percentage'         => ['required', 'numeric', 'min:0', 'max:100'],
            'total_marks'             => ['required', 'numeric', 'min:1'],
            'shuffle_questions'       => ['boolean'],
            'shuffle_options'         => ['boolean'],
            'show_result_instant'     => ['boolean'],
            'allow_review'            => ['boolean'],
            'negative_marking'        => ['boolean'],
            'negative_mark_rate'      => ['nullable', 'numeric', 'min:0'],
            'enforce_fullscreen'      => ['boolean'],
            'max_violations'          => ['required', 'integer', 'min:1', 'max:20'],
            'auto_submit_on_violation'=> ['boolean'],
            'start_time'              => ['nullable', 'date'],
            'end_time'                => ['nullable', 'date', 'after_or_equal:start_time'],
            'status'                  => ['required', 'in:draft,published,archived'],
        ]);

        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['shuffle_options'] = $request->boolean('shuffle_options');
        $validated['show_result_instant'] = $request->boolean('show_result_instant');
        $validated['allow_review'] = $request->boolean('allow_review');
        $validated['negative_marking'] = $request->boolean('negative_marking');
        $validated['enforce_fullscreen'] = $request->boolean('enforce_fullscreen');
        $validated['auto_submit_on_violation'] = $request->boolean('auto_submit_on_violation');
        $validated['is_active'] = $request->status === 'published';
        $validated['created_by'] = Auth::id();

        $exam = Exam::create($validated);

        AuditLogService::log('create_exam', 'Exam', $exam->id, ['code' => $exam->code, 'title' => $exam->title]);

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', "Exam '{$exam->title}' created successfully. You can now add questions or bulk import.");
    }

    public function show(Exam $exam)
    {
        $exam->load(['category', 'creator', 'questions.options']);
        $exam->loadCount(['questions', 'attempts']);

        $attempts = $exam->attempts()
            ->with('user')
            ->latest('started_at')
            ->paginate(15);

        $stats = [
            'total_candidates' => $exam->attempts()->distinct('user_id')->count('user_id'),
            'passed'           => $exam->attempts()->where('is_passed', true)->count(),
            'failed'           => $exam->attempts()->where('status', 'completed')->where('is_passed', false)->count(),
            'avg_score'        => round($exam->attempts()->where('status', 'completed')->avg('score') ?? 0, 2),
            'highest_score'    => round($exam->attempts()->where('status', 'completed')->max('score') ?? 0, 2),
        ];

        return view('admin.exams.show', compact('exam', 'attempts', 'stats'));
    }

    public function edit(Exam $exam)
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.exams.edit', compact('exam', 'categories'));
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'category_id'             => ['required', 'exists:categories,id'],
            'title'                   => ['required', 'string', 'max:255'],
            'code'                    => ['required', 'string', 'max:50', "unique:exams,code,{$exam->id}"],
            'description'             => ['nullable', 'string'],
            'instructions'            => ['nullable', 'string'],
            'duration_minutes'        => ['required', 'integer', 'min:1', 'max:600'],
            'pass_percentage'         => ['required', 'numeric', 'min:0', 'max:100'],
            'total_marks'             => ['required', 'numeric', 'min:1'],
            'shuffle_questions'       => ['boolean'],
            'shuffle_options'         => ['boolean'],
            'show_result_instant'     => ['boolean'],
            'allow_review'            => ['boolean'],
            'negative_marking'        => ['boolean'],
            'negative_mark_rate'      => ['nullable', 'numeric', 'min:0'],
            'enforce_fullscreen'      => ['boolean'],
            'max_violations'          => ['required', 'integer', 'min:1', 'max:20'],
            'auto_submit_on_violation'=> ['boolean'],
            'start_time'              => ['nullable', 'date'],
            'end_time'                => ['nullable', 'date', 'after_or_equal:start_time'],
            'status'                  => ['required', 'in:draft,published,archived'],
        ]);

        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['shuffle_options'] = $request->boolean('shuffle_options');
        $validated['show_result_instant'] = $request->boolean('show_result_instant');
        $validated['allow_review'] = $request->boolean('allow_review');
        $validated['negative_marking'] = $request->boolean('negative_marking');
        $validated['enforce_fullscreen'] = $request->boolean('enforce_fullscreen');
        $validated['auto_submit_on_violation'] = $request->boolean('auto_submit_on_violation');
        $validated['is_active'] = $request->status === 'published';

        $exam->update($validated);

        AuditLogService::log('update_exam', 'Exam', $exam->id, ['code' => $exam->code]);

        return redirect()->route('admin.exams.show', $exam)->with('success', 'Exam configuration updated.');
    }

    public function destroy(Exam $exam)
    {
        $title = $exam->title;
        $exam->delete();

        AuditLogService::log('delete_exam', 'Exam', null, ['title' => $title]);

        return redirect()->route('admin.exams.index')->with('success', "Exam '{$title}' removed.");
    }

    public function toggleStatus(Exam $exam)
    {
        $exam->is_active = !$exam->is_active;
        $exam->status = $exam->is_active ? 'published' : 'draft';
        $exam->save();

        return back()->with('success', "Exam status updated to {$exam->status}.");
    }

    public function exportResults(Exam $exam): StreamedResponse
    {
        $fileName = 'results_' . $exam->code . '_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($exam) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Attempt ID',
                'Candidate Name',
                'Student ID',
                'Email',
                'Status',
                'Total Questions',
                'Answered',
                'Correct',
                'Incorrect',
                'Score',
                'Percentage',
                'Passed',
                'Violations',
                'Started At',
                'Submitted At',
                'IP Address'
            ]);

            $attempts = ExamAttempt::with('user')
                ->where('exam_id', $exam->id)
                ->orderBy('id')
                ->cursor();

            foreach ($attempts as $att) {
                fputcsv($handle, [
                    $att->id,
                    $att->user->name ?? 'N/A',
                    $att->user->student_id ?? 'N/A',
                    $att->user->email ?? 'N/A',
                    $att->status,
                    $att->total_questions,
                    $att->answered_questions,
                    $att->correct_answers,
                    $att->incorrect_answers,
                    $att->score,
                    $att->percentage . '%',
                    $att->is_passed ? 'YES' : 'NO',
                    $att->violation_count,
                    $att->started_at ? $att->started_at->toDateTimeString() : '',
                    $att->submitted_at ? $att->submitted_at->toDateTimeString() : '',
                    $att->ip_address,
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
