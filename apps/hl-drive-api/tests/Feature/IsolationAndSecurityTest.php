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
use Illuminate\Support\Str;
use Tests\TestCase;

class IsolationAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $instructorA;
    protected User $studentA;
    protected User $instructorB;
    protected User $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        // Tenant A
        $this->tenantA = Tenant::factory()->create();
        $this->instructorA = User::factory()->create();
        $this->studentA = User::factory()->create();

        // Tenant B
        $this->tenantB = Tenant::factory()->create();
        $this->instructorB = User::factory()->create();
        $this->studentB = User::factory()->create();
    }

    public function test_invitations_isolated_by_tenant(): void
    {
        // Setup: Create invitations in both tenants
        $invitationA = Invitation::create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $this->studentA->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        $invitationB = Invitation::create([
            'tenant_id' => $this->tenantB->id,
            'instructor_id' => $this->instructorB->id,
            'student_id' => $this->studentB->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        // Verify: Tenant A can only see their invitations
        $invitationsInA = Invitation::where('tenant_id', $this->tenantA->id)->get();
        $this->assertCount(1, $invitationsInA);
        $this->assertTrue($invitationsInA->contains('id', $invitationA->id));
        $this->assertFalse($invitationsInA->contains('id', $invitationB->id));

        // Verify: Tenant B can only see their invitations
        $invitationsInB = Invitation::where('tenant_id', $this->tenantB->id)->get();
        $this->assertCount(1, $invitationsInB);
        $this->assertTrue($invitationsInB->contains('id', $invitationB->id));
        $this->assertFalse($invitationsInB->contains('id', $invitationA->id));
    }

    public function test_links_isolated_by_tenant(): void
    {
        // Setup: Create links in both tenants
        $linkA = InstructorStudentLink::create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $this->studentA->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        $linkB = InstructorStudentLink::create([
            'tenant_id' => $this->tenantB->id,
            'instructor_id' => $this->instructorB->id,
            'student_id' => $this->studentB->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        // Verify: Tenant A sees only their links
        $linksInA = InstructorStudentLink::where('tenant_id', $this->tenantA->id)->get();
        $this->assertCount(1, $linksInA);
        $this->assertTrue($linksInA->contains('id', $linkA->id));

        // Verify: Tenant B sees only their links
        $linksInB = InstructorStudentLink::where('tenant_id', $this->tenantB->id)->get();
        $this->assertCount(1, $linksInB);
        $this->assertTrue($linksInB->contains('id', $linkB->id));
    }

    public function test_instructor_cannot_see_other_instructor_students(): void
    {
        // Setup: Create links in same tenant but different instructors
        $linkA = InstructorStudentLink::create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $this->studentA->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        $instructor2A = User::factory()->create();
        $student2A = User::factory()->create();

        $linkAlt = InstructorStudentLink::create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $instructor2A->id,
            'student_id' => $student2A->id,
            'status' => LinkStatus::ACTIVE->value,
            'access_level' => AccessLevel::FULL->value,
        ]);

        // Verify: Instructor A sees only their links
        $instructorALinks = InstructorStudentLink::byInstructor($this->instructorA->id)
            ->active()
            ->get();

        $this->assertCount(1, $instructorALinks);
        $this->assertEquals($instructorALinks->first()->instructor_id, $this->instructorA->id);

        // Verify: Instructor 2A sees only their links
        $instructor2ALinks = InstructorStudentLink::byInstructor($instructor2A->id)
            ->active()
            ->get();

        $this->assertCount(1, $instructor2ALinks);
        $this->assertEquals($instructor2ALinks->first()->instructor_id, $instructor2A->id);
    }

    public function test_student_cannot_see_other_student_invitations(): void
    {
        // Setup: Create invitations for 2 students
        $inv1 = Invitation::create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $this->studentA->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        $student2A = User::factory()->create();

        $inv2 = Invitation::create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $student2A->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        // Verify: Student A sees only their invitations
        $studentAInvitations = Invitation::where('student_id', $this->studentA->id)->get();
        $this->assertCount(1, $studentAInvitations);
        $this->assertTrue($studentAInvitations->contains('id', $inv1->id));
        $this->assertFalse($studentAInvitations->contains('id', $inv2->id));

        // Verify: Student 2A sees only their invitations
        $student2AInvitations = Invitation::where('student_id', $student2A->id)->get();
        $this->assertCount(1, $student2AInvitations);
        $this->assertTrue($student2AInvitations->contains('id', $inv2->id));
        $this->assertFalse($student2AInvitations->contains('id', $inv1->id));
    }
}
