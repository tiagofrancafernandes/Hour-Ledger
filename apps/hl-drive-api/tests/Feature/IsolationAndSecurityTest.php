<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\InvitationStatus;
use App\Enums\LinkStatus;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    protected TenantResolver $tenantResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantResolver = app(TenantResolver::class);

        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();

        $this->tenantResolver->setTenantId($this->tenantA->id);
        $this->instructorA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->studentA = User::factory()->create(['tenant_id' => $this->tenantA->id]);

        $this->tenantResolver->setTenantId($this->tenantB->id);
        $this->instructorB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
        $this->studentB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
    }

    public function test_invitations_isolated_by_tenant(): void
    {
        $this->tenantResolver->setTenantId($this->tenantA->id);
        $invitationA = Invitation::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $this->studentA->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->tenantResolver->setTenantId($this->tenantB->id);
        $invitationB = Invitation::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'instructor_id' => $this->instructorB->id,
            'student_id' => $this->studentB->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitationsInA = Invitation::where('tenant_id', $this->tenantA->id)->get();
        $this->assertCount(1, $invitationsInA);
        $this->assertTrue($invitationsInA->contains('id', $invitationA->id));
        $this->assertFalse($invitationsInA->contains('id', $invitationB->id));

        $invitationsInB = Invitation::where('tenant_id', $this->tenantB->id)->get();
        $this->assertCount(1, $invitationsInB);
        $this->assertTrue($invitationsInB->contains('id', $invitationB->id));
        $this->assertFalse($invitationsInB->contains('id', $invitationA->id));
    }


    public function test_instructor_cannot_see_other_instructor_students(): void
    {
        $this->tenantResolver->setTenantId($this->tenantA->id);

        $linkA = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $this->studentA->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $instructor2A = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $student2A = User::factory()->create(['tenant_id' => $this->tenantA->id]);

        $linkAlt = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $instructor2A->id,
            'student_id' => $student2A->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $instructorALinks = InstructorStudentLink::byInstructor($this->instructorA->id)
            ->active()
            ->get();

        $this->assertCount(1, $instructorALinks);
        $this->assertEquals($instructorALinks->first()->instructor_id, $this->instructorA->id);

        $instructor2ALinks = InstructorStudentLink::byInstructor($instructor2A->id)
            ->active()
            ->get();

        $this->assertCount(1, $instructor2ALinks);
        $this->assertEquals($instructor2ALinks->first()->instructor_id, $instructor2A->id);
    }

    public function test_student_cannot_see_other_student_invitations(): void
    {
        $this->tenantResolver->setTenantId($this->tenantA->id);

        $inv1 = Invitation::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $this->studentA->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $student2A = User::factory()->create(['tenant_id' => $this->tenantA->id]);

        $inv2 = Invitation::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $this->instructorA->id,
            'student_id' => $student2A->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $studentAInvitations = Invitation::where('student_id', $this->studentA->id)->get();
        $this->assertCount(1, $studentAInvitations);
        $this->assertTrue($studentAInvitations->contains('id', $inv1->id));
        $this->assertFalse($studentAInvitations->contains('id', $inv2->id));

        $student2AInvitations = Invitation::where('student_id', $student2A->id)->get();
        $this->assertCount(1, $student2AInvitations);
        $this->assertTrue($student2AInvitations->contains('id', $inv2->id));
        $this->assertFalse($student2AInvitations->contains('id', $inv1->id));
    }
}
