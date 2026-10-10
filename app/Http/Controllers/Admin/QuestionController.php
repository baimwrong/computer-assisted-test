<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::with(['exam', 'category', 'options']);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('search')) {
            $query->where('question_text', 'ilike', "%{$request->search}%");
        }

        $questions = $query->latest()->paginate(15);
        $exams = Exam::orderBy('title')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.questions.index', compact('questions', 'exams', 'categories'));
    }

    public function create(Request $request)
    {
        $exams = Exam::orderBy('title')->get();
        $categories = Category::orderBy('name')->get();
        $selectedExamId = $request->query('exam_id');

        return view('admin.questions.create', compact('exams', 'categories', 'selectedExamId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id'         => ['nullable', 'exists:exams,id'],
            'category_id'     => ['nullable', 'exists:categories,id'],
            'question_text'   => ['required', 'string'],
            'question_type'   => ['required', 'in:single_choice,multiple_choice,true_false,short_answer'],
            'points'          => ['required', 'numeric', 'min:0.1'],
            'negative_points' => ['nullable', 'numeric', 'min:0'],
            'difficulty'      => ['required', 'in:easy,medium,hard'],
            'explanation'     => ['nullable', 'string'],
        ]);

        $validated['negative_points'] = $validated['negative_points'] ?? 0;
        $validated['is_active'] = true;

        DB::transaction(function () use ($request, $validated) {
            $question = Question::create($validated);

            $type = $validated['question_type'];

            if ($type === 'true_false') {
                $correct = $request->input('tf_correct', 'true');
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => 'True',
                    'is_correct'  => $correct === 'true',
                    'order_index' => 1,
                ]);
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => 'False',
                    'is_correct'  => $correct === 'false',
                    'order_index' => 2,
                ]);
            } elseif ($type === 'short_answer') {
                $answers = explode('|', $request->input('short_answers', ''));
                foreach ($answers as $idx => $ans) {
                    if (trim($ans) !== '') {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => trim($ans),
                            'is_correct'  => true,
                            'order_index' => $idx + 1,
                        ]);
                    }
                }
            } else {
                // single_choice or multiple_choice
                $options = $request->input('options', []);
                $correctOptions = (array) $request->input('correct_options', []);

                $order = 1;
                foreach ($options as $key => $optText) {
                    if (trim($optText) !== '') {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => trim($optText),
                            'is_correct'  => in_array((string)$key, array_map('strval', $correctOptions), true),
                            'order_index' => $order++,
                        ]);
                    }
                }
            }

            AuditLogService::log('create_question', 'Question', $question->id, [
                'type' => $question->question_type,
                'exam_id' => $question->exam_id,
            ]);
        });

        $redirectRoute = $request->filled('exam_id')
            ? redirect()->route('admin.exams.show', $request->exam_id)
            : redirect()->route('admin.questions.index');

        return $redirectRoute->with('success', 'Question created successfully.');
    }

    public function edit(Question $question)
    {
        $question->load('options');
        $exams = Exam::orderBy('title')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.questions.edit', compact('question', 'exams', 'categories'));
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'exam_id'         => ['nullable', 'exists:exams,id'],
            'category_id'     => ['nullable', 'exists:categories,id'],
            'question_text'   => ['required', 'string'],
            'question_type'   => ['required', 'in:single_choice,multiple_choice,true_false,short_answer'],
            'points'          => ['required', 'numeric', 'min:0.1'],
            'negative_points' => ['nullable', 'numeric', 'min:0'],
            'difficulty'      => ['required', 'in:easy,medium,hard'],
            'explanation'     => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $question, $validated) {
            $question->update($validated);
            $question->options()->delete();

            $type = $validated['question_type'];

            if ($type === 'true_false') {
                $correct = $request->input('tf_correct', 'true');
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => 'True',
                    'is_correct'  => $correct === 'true',
                    'order_index' => 1,
                ]);
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => 'False',
                    'is_correct'  => $correct === 'false',
                    'order_index' => 2,
                ]);
            } elseif ($type === 'short_answer') {
                $answers = explode('|', $request->input('short_answers', ''));
                foreach ($answers as $idx => $ans) {
                    if (trim($ans) !== '') {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => trim($ans),
                            'is_correct'  => true,
                            'order_index' => $idx + 1,
                        ]);
                    }
                }
            } else {
                $options = $request->input('options', []);
                $correctOptions = (array) $request->input('correct_options', []);

                $order = 1;
                foreach ($options as $key => $optText) {
                    if (trim($optText) !== '') {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => trim($optText),
                            'is_correct'  => in_array((string)$key, array_map('strval', $correctOptions), true),
                            'order_index' => $order++,
                        ]);
                    }
                }
            }

            AuditLogService::log('update_question', 'Question', $question->id);
        });

        return redirect()->route('admin.questions.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question)
    {
        $id = $question->id;
        $question->delete();

        AuditLogService::log('delete_question', 'Question', $id);

        return back()->with('success', 'Question deleted.');
    }
}
