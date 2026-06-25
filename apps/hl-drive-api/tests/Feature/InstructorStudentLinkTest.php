<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\InvitationStatus;
use App\Enums\LinkStatus;
use App\Enums\AccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorStudentLinkTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $instructor;
    protected User $student;
    protected Invitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->instructor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->student = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::ACCEPTED,
        ]);
    }

    public function test_link_becomes_active_after_accepting_invitation(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'invitation_id' => $this->invitation->id,
            'status' => LinkStatus::ACTIVE,
            'access_level' => AccessLevel::FULL,
        ]);

        $this->assertDatabaseHas('instructor_student_links', [
            'id' => $link->id,
            'status' => LinkStatus::ACTIVE->value,
        ]);

        $this->assertTrue($link->isActive());
        $this->assertTrue($link->grantAccess());
    }

    public function test_student_sets_link_instructor_as_active(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->student->update(['active_instructor_id' => $this->instructor->id]);

        $this->assertEquals($this->student->fresh()->active_instructor_id, $this->instructor->id);
    }

    public function test_cannot_create_duplicate_active_link(): void
    {
        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);
    }

    public function test_link_related_data_loads_correctly(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'invitation_id' => $this->invitation->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $loadedLink = InstructorStudentLink::with(['instructor', 'student', 'invitation'])->find($link->id);

        $this->assertNotNull($loadedLink->instructor);
        $this->assertNotNull($loadedLink->student);
        $this->assertNotNull($loadedLink->invitation);

        $this->assertEquals($loadedLink->instructor->id, $this->instructor->id);
        $this->assertEquals($loadedLink->student->id, $this->student->id);
        $this->assertEquals($loadedLink->invitation->id, $this->invitation->id);
    }

    public function test_revoke_link_blocks_access(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->assertTrue($link->grantAccess());

        $link->revoke();

        $this->assertFalse($link->fresh()->grantAccess());
        $this->assertTrue($link->fresh()->status === LinkStatus::REVOKED);
        $this->assertNotNull($link->fresh()->revoked_at);
        $this->assertNotNull($link->fresh()->deleted_at);
    }

    public function test_revoked_link_soft_deletes(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $linkId = $link->id;

        $link->revoke();

        $this->assertSoftDeleted('instructor_student_links', ['id' => $linkId]);

        $this->assertNull(InstructorStudentLink::find($linkId));
        $this->assertNotNull(InstructorStudentLink::withTrashed()->find($linkId));
    }

    public function test_suspend_link_temporarily(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $link->suspend();

        $this->assertTrue($link->fresh()->status === LinkStatus::SUSPENDED);
        $this->assertNull($link->fresh()->deleted_at);
    }

    public function test_soft_deleted_links_not_listed(): void
    {
        $activeLink = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $otherStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $revokedLink = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $revokedLink->revoke();

        $activeLinks = InstructorStudentLink::active()->get();

        $this->assertCount(1, $activeLinks);
        $this->assertTrue($activeLinks->first()->id === $activeLink->id);
        $this->assertFalse($activeLinks->pluck('id')->contains($revokedLink->id));
    }

    public function test_instructor_can_list_only_own_students(): void
    {
        $otherInstructor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $otherInstructor->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $instructorStudents = InstructorStudentLink::byInstructor($this->instructor->id)->active()->get();

        $this->assertCount(1, $instructorStudents);
        $this->assertEquals($instructorStudents->first()->instructor_id, $this->instructor->id);
    }

    public function test_student_can_list_only_own_instructors(): void
    {
        $otherStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherInstructor = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $otherInstructor->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $studentInstructors = InstructorStudentLink::byStudent($this->student->id)->active()->get();

        $this->assertCount(1, $studentInstructors);
        $this->assertEquals($studentInstructors->first()->student_id, $this->student->id);
    }

    public function test_cannot_access_other_instructor_links(): void
    {
        $otherInstructor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $otherLink = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $otherInstructor->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $myLinks = InstructorStudentLink::byInstructor($this->instructor->id)->active()->get();

        $this->assertFalse($myLinks->pluck('id')->contains($otherLink->id));
    }

    public function test_can_have_multiple_instructors(): void
    {
        $instructor2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $instructor3 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor3->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $studentInstructors = InstructorStudentLink::byStudent($this->student->id)->active()->get();

        $this->assertCount(3, $studentInstructors);
    }
}
