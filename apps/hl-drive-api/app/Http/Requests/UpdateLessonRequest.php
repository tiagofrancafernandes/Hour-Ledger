<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
