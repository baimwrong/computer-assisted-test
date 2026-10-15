<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
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
            $term = '%' . strtolower($request->search) . '%';
            $query->where(DB::raw('LOWER(question_text)'), 'LIKE', $term);
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

    public function store(StoreQuestionRequest $request)
    {
        $validated = $request->validated();
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

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $question, $validated) {
            $question->update($validated);

            $type = $validated['question_type'];
            $existingOptions = $question->options->keyBy('id');
            $keptIds = [];

            if ($type === 'true_false') {
                $correct = $request->input('tf_correct', 'true');
                
                $trueOpt = $question->options->firstWhere('order_index', 1) 
                    ?? $question->options->firstWhere('option_text', 'True');
                $falseOpt = $question->options->firstWhere('order_index', 2) 
                    ?? $question->options->firstWhere('option_text', 'False');

                if ($trueOpt) {
                    $trueOpt->update(['option_text' => 'True', 'is_correct' => $correct === 'true', 'order_index' => 1]);
                    $keptIds[] = $trueOpt->id;
                } else {
                    $newTrue = QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => 'True',
                        'is_correct'  => $correct === 'true',
                        'order_index' => 1,
                    ]);
                    $keptIds[] = $newTrue->id;
                }

                if ($falseOpt) {
                    $falseOpt->update(['option_text' => 'False', 'is_correct' => $correct === 'false', 'order_index' => 2]);
                    $keptIds[] = $falseOpt->id;
                } else {
                    $newFalse = QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => 'False',
                        'is_correct'  => $correct === 'false',
                        'order_index' => 2,
                    ]);
                    $keptIds[] = $newFalse->id;
                }
            } elseif ($type === 'short_answer') {
                $answers = array_values(array_filter(array_map('trim', explode('|', $request->input('short_answers', '')))));
                $existingList = $question->options->values();

                foreach ($answers as $idx => $ans) {
                    $existingItem = $existingList[$idx] ?? null;
                    if ($existingItem) {
                        $existingItem->update([
                            'option_text' => $ans,
                            'is_correct'  => true,
                            'order_index' => $idx + 1,
                        ]);
                        $keptIds[] = $existingItem->id;
                    } else {
                        $newOpt = QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $ans,
                            'is_correct'  => true,
                            'order_index' => $idx + 1,
                        ]);
                        $keptIds[] = $newOpt->id;
                    }
                }
            } else {
                $options = $request->input('options', []);
                $optionIds = $request->input('option_ids', []);
                $correctOptions = (array) $request->input('correct_options', []);

                $order = 1;
                foreach ($options as $key => $optText) {
                    if (trim($optText) === '') {
                        continue;
                    }

                    $optId = isset($optionIds[$key]) ? (int) $optionIds[$key] : null;
                    $isCorrect = in_array((string)$key, array_map('strval', $correctOptions), true);

                    if ($optId && $existingOptions->has($optId)) {
                        $existingOptions[$optId]->update([
                            'option_text' => trim($optText),
                            'is_correct'  => $isCorrect,
                            'order_index' => $order++,
                        ]);
                        $keptIds[] = $optId;
                    } else {
                        $newOpt = QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => trim($optText),
                            'is_correct'  => $isCorrect,
                            'order_index' => $order++,
                        ]);
                        $keptIds[] = $newOpt->id;
                    }
                }
            }

            // Remove any options that were dropped without deleting surviving options
            $question->options()->whereNotIn('id', $keptIds)->delete();

            AuditLogService::log('update_question', 'Question', $question->id);
        });

        return redirect()->route('admin.questions.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question)
    {
        if ($question->attemptAnswers()->exists()) {
            return back()->with('error', 'Cannot delete this question because candidate answers exist for it. Deactivate it instead.');
        }

        $id = $question->id;
        $question->delete();

        AuditLogService::log('delete_question', 'Question', $id);

        return back()->with('success', 'Question deleted.');
    }
}
