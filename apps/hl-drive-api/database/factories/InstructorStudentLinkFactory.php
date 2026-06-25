<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InstructorStudentLink;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Tenant;
use App\Enums\LinkStatus;
use App\Enums\AccessLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InstructorStudentLink>
 */
class InstructorStudentLinkFactory extends Factory
{
    protected $model = InstructorStudentLink::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $invitation = Invitation::factory()->create(['tenant_id' => $tenant->id]);
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'invitation_id' => $invitation->id,
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LinkStatus::ACTIVE,
            'access_level' => AccessLevel::FULL,
        ];
    }

    /**
     * Set the link as suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => LinkStatus::SUSPENDED,
        ]);
    }

    /**
     * Set the link as revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => LinkStatus::REVOKED,
        ]);
    }
}
