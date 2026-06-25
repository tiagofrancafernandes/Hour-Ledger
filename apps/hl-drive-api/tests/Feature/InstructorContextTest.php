<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\LinkStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorContextTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $instructor;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->instructor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->student = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);
    }

    public function test_instructor_context_filters_resources(): void
    {
        $otherInstructor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $otherInstructor->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $linksForInstructor = InstructorStudentLink::byInstructor($this->instructor->id)
            ->active()
            ->get();

        $this->assertCount(1, $linksForInstructor);
        $this->assertEquals($linksForInstructor->first()->instructor_id, $this->instructor->id);
    }

    public function test_switching_active_instructor_filters_queries(): void
    {
        $instructor2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $student2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor2->id,
            'student_id' => $student2->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->student->update(['active_instructor_id' => $this->instructor->id]);

        $activeInstructor1Links = InstructorStudentLink::byInstructor($this->student->active_instructor_id)
            ->active()
            ->get();

        $this->assertCount(1, $activeInstructor1Links);
        $this->assertEquals($activeInstructor1Links->first()->instructor_id, $this->instructor->id);

        $this->student->update(['active_instructor_id' => $instructor2->id]);

        $activeInstructor2Links = InstructorStudentLink::byInstructor($this->student->active_instructor_id)
            ->active()
            ->get();

        $this->assertCount(1, $activeInstructor2Links);
        $this->assertEquals($activeInstructor2Links->first()->instructor_id, $instructor2->id);
    }

    public function test_cross_instructor_access_blocked(): void
    {
        $instructor2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $links = InstructorStudentLink::byInstructor($instructor2->id)
            ->byStudent($this->student->id)
            ->active()
            ->get();

        $this->assertCount(0, $links);
    }

    public function test_without_context_resources_empty(): void
    {
        $unlinkedStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $instructorLinks = InstructorStudentLink::byStudent($unlinkedStudent->id)->active()->get();

        $this->assertCount(0, $instructorLinks);
    }

    public function test_context_persists_in_session(): void
    {
        $this->student->update(['active_instructor_id' => $this->instructor->id]);

        $freshStudent = User::find($this->student->id);

        $this->assertEquals($freshStudent->active_instructor_id, $this->instructor->id);
    }

    public function test_queries_with_scope_filter_correctly(): void
    {
        $otherStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $instructorLinks = InstructorStudentLink::byInstructor($this->instructor->id)->active()->get();

        $this->assertCount(2, $instructorLinks);

        $studentLinks = InstructorStudentLink::byStudent($this->student->id)->active()->get();

        $this->assertCount(1, $studentLinks);
    }

    public function test_active_instructor_change_updates_header(): void
    {
        $instructor2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $student2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->student->update(['active_instructor_id' => $this->instructor->id]);

        $this->assertEquals($this->student->fresh()->active_instructor_id, $this->instructor->id);

        $this->student->update(['active_instructor_id' => $instructor2->id]);

        $this->assertEquals($this->student->fresh()->active_instructor_id, $instructor2->id);
    }

    public function test_desvinculation_removes_access(): void
    {
        $this->student->update(['active_instructor_id' => $this->instructor->id]);

        $link = InstructorStudentLink::byInstructor($this->instructor->id)->byStudent($this->student->id)->first();
        $link->revoke();

        $this->assertNull(InstructorStudentLink::find($link->id));
    }
}
