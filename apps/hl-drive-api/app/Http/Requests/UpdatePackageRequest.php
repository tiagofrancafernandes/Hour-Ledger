<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'hours' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
