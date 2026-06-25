<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Tenant;
use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'token' => Str::random(32),
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Set the invitation as accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Set the invitation as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::REJECTED,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Set the invitation as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
