<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'student_id' => ['nullable', 'string', 'max:50', 'unique:users,student_id'],
            'password'   => ['required', 'string', 'min:6'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'role'       => ['required', 'in:candidate,proctor,admin'],
        ];
    }
}
