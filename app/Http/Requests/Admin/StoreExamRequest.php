<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'category_id'              => ['required', 'exists:categories,id'],
            'title'                    => ['required', 'string', 'max:255'],
            'code'                     => ['required', 'string', 'max:50', 'unique:exams,code'],
            'description'              => ['nullable', 'string'],
            'instructions'             => ['nullable', 'string'],
            'duration_minutes'         => ['required', 'integer', 'min:1', 'max:600'],
            'pass_percentage'          => ['required', 'numeric', 'min:0', 'max:100'],
            'total_marks'              => ['required', 'numeric', 'min:1'],
            'shuffle_questions'        => ['boolean'],
            'shuffle_options'          => ['boolean'],
            'show_result_instant'      => ['boolean'],
            'allow_review'             => ['boolean'],
            'negative_marking'         => ['boolean'],
            'negative_mark_rate'       => ['nullable', 'numeric', 'min:0'],
            'enforce_fullscreen'       => ['boolean'],
            'max_violations'           => ['required', 'integer', 'min:1', 'max:20'],
            'auto_submit_on_violation' => ['boolean'],
            'start_time'               => ['nullable', 'date'],
            'end_time'                 => ['nullable', 'date', 'after_or_equal:start_time'],
            'status'                   => ['required', 'in:draft,published,archived'],
        ];
    }

    public function payload(): array
    {
        $data = $this->validated();
        $data['shuffle_questions'] = $this->boolean('shuffle_questions');
        $data['shuffle_options'] = $this->boolean('shuffle_options');
        $data['show_result_instant'] = $this->boolean('show_result_instant');
        $data['allow_review'] = $this->boolean('allow_review');
        $data['negative_marking'] = $this->boolean('negative_marking');
        $data['enforce_fullscreen'] = $this->boolean('enforce_fullscreen');
        $data['auto_submit_on_violation'] = $this->boolean('auto_submit_on_violation');
        $data['is_active'] = $this->status === 'published';
        $data['created_by'] = $this->user()->id;

        return $data;
    }
}
