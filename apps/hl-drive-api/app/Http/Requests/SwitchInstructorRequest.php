<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchInstructorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',
                'exists:tenants,id',
            ],
            'instructor_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:' . ($this->user()?->id ?? 'null'),
            ],
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.integer' => 'Tenant ID must be an integer',
            'tenant_id.exists' => 'Tenant not found',
            'instructor_id.required' => 'Instructor ID is required',
            'instructor_id.integer' => 'Instructor ID must be an integer',
            'instructor_id.exists' => 'Instructor not found',
            'instructor_id.different' => 'Cannot switch to yourself',
        ];
    }
}
