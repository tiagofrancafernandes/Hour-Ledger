<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'instructor_id' => $this->instructor_id,
            'instructor' => new UserResource($this->whenLoaded('instructor')),
            'student_id' => $this->student_id,
            'student' => new UserResource($this->whenLoaded('student')),
            'email' => $this->email,
            'status' => $this->status->value,
            'expires_at' => $this->expires_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
