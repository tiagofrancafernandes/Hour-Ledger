<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\InvitationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationFlowTest extends TestCase
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
    }

    public function test_instructor_can_create_invitation_to_student(): void
    {
        $this->assertDatabaseMissing('invitations', [
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ]);

        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING->value,
        ]);
    }

    public function test_invitation_expires_after_7_days(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'expires_at' => now()->addDays(7),
            'status' => InvitationStatus::PENDING,
        ]);

        $this->assertFalse($invitation->isExpired());

        $expiredInvitation = $invitation->replicate();
        $expiredInvitation->expires_at = now()->subMinutes(1);
        $expiredInvitation->save();

        $this->assertTrue($expiredInvitation->isExpired());
    }

    public function test_invitation_token_is_unique(): void
    {
        $token = 'unique-token-12345';

        $invitation1 = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'token' => $token,
        ]);

        $this->assertTrue($invitation1->exists);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'token' => $token,
        ]);
    }

    public function test_student_can_accept_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->assertTrue($invitation->isPending());
        $this->assertTrue($invitation->isResolvable());

        $invitation->accept();

        $this->assertFalse($invitation->isPending());
        $this->assertTrue($invitation->status === InvitationStatus::ACCEPTED);
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'expires_at' => now()->subMinutes(1),
            'status' => InvitationStatus::PENDING,
        ]);

        $this->assertTrue($invitation->isExpired());
        $this->assertFalse($invitation->isResolvable());
    }

    public function test_student_can_reject_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitation->reject();

        $this->assertTrue($invitation->status === InvitationStatus::REJECTED);
        $this->assertNotNull($invitation->rejected_at);
    }

    public function test_cannot_accept_twice(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitation->accept();

        $this->assertFalse($invitation->isResolvable());
        $this->assertTrue($invitation->status === InvitationStatus::ACCEPTED);
    }

    public function test_cannot_create_duplicate_pending_invitation(): void
    {
        Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);
    }

    public function test_can_resend_invitation(): void
    {
        $invitation1 = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitation1->reject();

        $invitation2 = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->assertNotEquals($invitation1->id, $invitation2->id);
        $this->assertTrue($invitation1->status === InvitationStatus::REJECTED);
        $this->assertTrue($invitation2->status === InvitationStatus::PENDING);
    }

    public function test_invitation_soft_deletes(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ]);

        $invitationId = $invitation->id;

        $invitation->delete();

        $this->assertSoftDeleted('invitations', ['id' => $invitationId]);

        $this->assertNull(Invitation::find($invitationId));
        $this->assertNotNull(Invitation::withTrashed()->find($invitationId));
    }
}
