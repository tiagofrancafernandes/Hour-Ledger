<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'instructor_id' => User::factory(),
            'student_id' => User::factory(),
            'wallet_id' => null,
            'package_id' => null,
            'ledger_entry_id' => null,
            'scheduled_at' => now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0),
            'duration_minutes' => 50,
            'status' => 'scheduled',
            'completed_at' => null,
            'cancelled_at' => null,
            'hours_consumed' => null,
            'notes' => fake()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'hours_consumed' => 1.00,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
