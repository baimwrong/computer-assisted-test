<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exam;
use App\Services\BulkImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkImportController extends Controller
{
    public function __construct(
        protected BulkImportService $importService
    ) {}

    public function index()
    {
        $exams = Exam::orderBy('title')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.bulk-import.index', compact('exams', 'categories'));
    }

    public function importQuestions(Request $request)
    {
        $request->validate([
            'csv_file'    => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'exam_id'     => ['nullable', 'exists:exams,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $result = $this->importService->importQuestions(
            $request->file('csv_file'),
            $request->filled('exam_id') ? (int) $request->exam_id : null,
            $request->filled('category_id') ? (int) $request->category_id : null
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $imported = $result['imported'];
        $errorCount = count($result['errors']);

        return back()
            ->with('import_result', [
                'type' => 'questions',
                'imported' => $imported,
                'errors' => $result['errors'],
            ])
            ->with('success', "Bulk question import processed: {$imported} questions successfully added" . ($errorCount > 0 ? " with {$errorCount} warnings/errors." : "."));
    }

    public function importCandidates(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $result = $this->importService->importCandidates($request->file('csv_file'));

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $imported = $result['imported'];
        $errorCount = count($result['errors']);

        return back()
            ->with('import_result', [
                'type' => 'candidates',
                'imported' => $imported,
                'errors' => $result['errors'],
            ])
            ->with('success', "Bulk candidate import processed: {$imported} candidates created/updated" . ($errorCount > 0 ? " with {$errorCount} errors." : "."));
    }

    public function downloadQuestionTemplate(): Response
    {
        $csv = $this->importService->getQuestionTemplateCsv();
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cat_questions_template.csv"',
        ]);
    }

    public function downloadCandidateTemplate(): Response
    {
        $csv = $this->importService->getCandidateTemplateCsv();
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cat_candidates_template.csv"',
        ]);
    }
}
