<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvitationRequest extends FormRequest
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
            'recipient_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                'different:' . ($this->user()?->id ?? 'null'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
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
            'recipient_id.integer' => 'Recipient ID must be an integer',
            'recipient_id.exists' => 'Recipient user not found',
            'recipient_id.different' => 'Cannot send invitation to yourself',
            'email.email' => 'Email must be a valid email address',
            'email.max' => 'Email cannot exceed 255 characters',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Ensure at least one of recipient_id or email is provided
        if (empty($this->input('recipient_id')) && empty($this->input('email'))) {
            $this->merge([
                'email' => $this->input('email') ?? null,
            ]);
        }
    }

    /**
     * Perform additional validation after validation passes.
     *
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $recipientId = $this->input('recipient_id');
            $email = $this->input('email');

            // Require at least one of recipient_id or email
            if (!$recipientId && !$email) {
                $validator->errors()->add('recipient_id', 'Either recipient_id or email must be provided');
            }

            // Cannot provide both recipient_id and email
            if ($recipientId && $email) {
                $validator->errors()->add('email', 'Cannot provide both recipient_id and email');
            }
        });
    }
}
