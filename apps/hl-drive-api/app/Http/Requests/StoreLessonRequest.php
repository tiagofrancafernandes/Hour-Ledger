<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'notes' => ['nullable', 'string'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
