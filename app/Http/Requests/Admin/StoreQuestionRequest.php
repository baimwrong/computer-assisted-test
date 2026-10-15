<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'exam_id'         => ['nullable', 'exists:exams,id'],
            'category_id'     => ['nullable', 'exists:categories,id'],
            'question_text'   => ['required', 'string'],
            'question_type'   => ['required', 'in:single_choice,multiple_choice,true_false,short_answer'],
            'points'          => ['required', 'numeric', 'min:0.1'],
            'negative_points' => ['nullable', 'numeric', 'min:0'],
            'difficulty'      => ['required', 'in:easy,medium,hard'],
            'explanation'     => ['nullable', 'string'],
            'tf_correct'      => ['nullable', 'in:true,false'],
            'short_answers'   => ['nullable', 'string'],
            'options'         => ['nullable', 'array'],
            'options.*'       => ['nullable', 'string'],
            'correct_options' => ['nullable'],
        ];
    }
}
