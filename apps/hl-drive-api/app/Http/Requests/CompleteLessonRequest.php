<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hours_consumed' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
        ];
    }
}
