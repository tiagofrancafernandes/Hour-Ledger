<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\InvitationStatus;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationAcceptanceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $instructor;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $tenantResolver = app(TenantResolver::class);
        $tenantResolver->setTenantId($this->tenant->id);

        $this->instructor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->student = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function testAcceptingInvitationTransitionsToAcceptedState(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertTrue($invitation->status === InvitationStatus::PENDING);

        $invitation->accept();

        $this->assertTrue($invitation->status === InvitationStatus::ACCEPTED);
        $this->assertNotNull($invitation->accepted_at);
    }

    public function testRejectingInvitationDoesNotCreateLink(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->addDays(7),
        ]);

        $invitation->reject();

        $this->assertTrue($invitation->status === InvitationStatus::REJECTED);
        $this->assertNotNull($invitation->rejected_at);

        $this->assertCount(0, InstructorStudentLink::where('student_id', $this->student->id)->get());
    }

    public function testCannotAcceptExpiredInvitation(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->subMinutes(1),
        ]);

        $this->assertTrue($invitation->isExpired());
        $this->assertFalse($invitation->isResolvable());
    }

    public function testMultipleInvitationsCanBeSentToSameStudent(): void
    {
        $inv1 = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $instructor2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $inv2 = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor2->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $studentInvitations = Invitation::where('student_id', $this->student->id)->get();
        $this->assertCount(2, $studentInvitations);

        $this->assertTrue($inv1->status === InvitationStatus::PENDING);
        $this->assertTrue($inv2->status === InvitationStatus::PENDING);
    }
}
