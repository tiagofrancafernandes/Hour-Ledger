<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\LinkStatus;
use App\Enums\AccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiInstructorFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $instructor1;
    protected User $instructor2;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->instructor1 = User::factory()->create();
        $this->instructor2 = User::factory()->create();
        $this->student = User::factory()->create();
    }

    public function test_student_can_manage_links_with_multiple_instructors(): void
    {
        // Setup: Create links with 2 instructors
        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        // Verify: Student has 2 active links
        $studentLinks = InstructorStudentLink::where('student_id', $this->student->id)
            ->active()
            ->get();

        $this->assertCount(2, $studentLinks);
        $this->assertTrue($studentLinks->contains('instructor_id', $this->instructor1->id));
        $this->assertTrue($studentLinks->contains('instructor_id', $this->instructor2->id));
    }

    public function test_student_can_switch_active_instructor(): void
    {
        // Setup: Student connected to 2 instructors
        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        // Action: Switch active instructor
        $this->student->update(['active_instructor_id' => $this->instructor2->id]);
        $this->student->refresh();

        // Verify: Active context changed
        $this->assertEquals($this->student->active_instructor_id, $this->instructor2->id);

        // Switch back
        $this->student->update(['active_instructor_id' => $this->instructor1->id]);
        $this->student->refresh();

        // Verify: Switched back
        $this->assertEquals($this->student->active_instructor_id, $this->instructor1->id);
    }

    public function test_instructor_sees_only_own_student_links(): void
    {
        // Setup: Create links
        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        $otherStudent = User::factory()->create();

        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor2->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        // Verify: Instructor 1 sees only their links
        $inst1Links = InstructorStudentLink::byInstructor($this->instructor1->id)
            ->active()
            ->get();

        $this->assertCount(1, $inst1Links);
        $this->assertEquals($inst1Links->first()->instructor_id, $this->instructor1->id);

        // Verify: Instructor 2 sees only their links
        $inst2Links = InstructorStudentLink::byInstructor($this->instructor2->id)
            ->active()
            ->get();

        $this->assertCount(1, $inst2Links);
        $this->assertEquals($inst2Links->first()->instructor_id, $this->instructor2->id);
    }

    public function test_revoking_link_removes_instructor_access(): void
    {
        // Setup: Create active link
        $link = InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        $this->assertCount(1, InstructorStudentLink::where('student_id', $this->student->id)->active()->get());

        // Action: Revoke link
        $link->update(['status' => LinkStatus::REVOKED->value]);

        // Verify: Link no longer active
        $this->assertCount(0, InstructorStudentLink::where('student_id', $this->student->id)->active()->get());

        // Verify: Data still exists (not deleted)
        $this->assertCount(1, InstructorStudentLink::where('student_id', $this->student->id)->get());
    }

    public function test_multiple_active_instructors_for_one_student(): void
    {
        // Setup: Create multiple instructor links
        $instructor3 = User::factory()->create();

        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        InstructorStudentLink::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor3->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        // Verify: Student has 3 active links
        $studentLinks = InstructorStudentLink::where('student_id', $this->student->id)
            ->active()
            ->get();

        $this->assertCount(3, $studentLinks);
    }
}
