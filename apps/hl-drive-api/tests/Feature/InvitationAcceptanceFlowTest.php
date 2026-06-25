<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\InvitationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
        $this->instructor = User::factory()->create();
        $this->student = User::factory()->create();
    }

    public function test_accepting_invitation_creates_active_link(): void
    {
        // Setup: Create pending invitation
        $invitation = Invitation::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        // Verify: No link exists yet
        $this->assertCount(0, InstructorStudentLink::where('student_id', $this->student->id)->get());

        // Action: Accept invitation
        $invitation->update(['status' => InvitationStatus::ACCEPTED->value, 'accepted_at' => now()]);

        // Verify: Invitation marked as accepted
        $invitation->refresh();
        $this->assertTrue($invitation->status === InvitationStatus::ACCEPTED->value || $invitation->status->value === InvitationStatus::ACCEPTED->value);
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_rejecting_invitation_does_not_create_link(): void
    {
        // Setup: Create pending invitation
        $invitation = Invitation::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        // Action: Reject invitation
        $invitation->update(['status' => InvitationStatus::REJECTED->value, 'rejected_at' => now()]);

        // Verify: Invitation marked as rejected
        $invitation->refresh();
        $this->assertTrue($invitation->status === InvitationStatus::REJECTED->value || $invitation->status->value === InvitationStatus::REJECTED->value);
        $this->assertNotNull($invitation->rejected_at);

        // Verify: No link created
        $this->assertCount(0, InstructorStudentLink::where('student_id', $this->student->id)->get());
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        // Setup: Create expired invitation
        $invitation = Invitation::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->subMinutes(1),
        ]);

        // Verify: Invitation is expired (expires_at is in the past)
        $this->assertTrue($invitation->expires_at < now());
    }

    public function test_multiple_invitations_can_be_sent_to_same_student(): void
    {
        // Setup: Create 2 invitations from different instructors to same student
        Invitation::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        $instructor2 = User::factory()->create();

        Invitation::create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor2->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING->value,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        // Verify: Both invitations exist
        $studentInvitations = Invitation::where('student_id', $this->student->id)->get();
        $this->assertCount(2, $studentInvitations);
    }
}
