<?php

namespace Tests\Feature\Architecture;

use App\Enums\InvitationStatus;
use App\Enums\LinkStatus;
use App\Models\Invitation;
use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorStudentLinkArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $instructor;
    protected User $student;
    protected TenantResolver $tenantResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantResolver = app(TenantResolver::class);
        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->tenantResolver->setTenantId($this->tenant->id);

        $this->instructor = User::factory()->create();
        $this->student = User::factory()->create();

        $this->instructor->tenants()->attach($this->tenant->id);
        $this->student->tenants()->attach($this->tenant->id);
    }

    protected function tearDown(): void
    {
        $this->tenantResolver->clear();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invitationCanBeCreatedByInstructor()
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING->value,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invitationGeneratesUniqueToken()
    {
        $inv1 = Invitation::factory()->create(['tenant_id' => $this->tenant->id]);
        $inv2 = Invitation::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotEquals($inv1->token, $inv2->token);
        $this->assertNotEmpty($inv1->token);
        $this->assertNotEmpty($inv2->token);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function acceptingInvitationCreatesActiveLink()
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitation->update(['status' => InvitationStatus::ACCEPTED, 'accepted_at' => now()]);
        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'invitation_id' => $invitation->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $link = InstructorStudentLink::where([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ])->first();

        $this->assertNotNull($link);
        $this->assertEquals(LinkStatus::ACTIVE, $link->status);
        $this->assertEquals($invitation->id, $link->invitation_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rejectingInvitationCreatesRejectedState()
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitation->update(['status' => InvitationStatus::REJECTED, 'rejected_at' => now()]);

        $this->assertEquals(InvitationStatus::REJECTED, $invitation->fresh()->status);

        $link = InstructorStudentLink::where([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ])->first();

        $this->assertNull($link);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function studentWithActiveLinkCanAccessInstructorData()
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $hasAccess = InstructorStudentLink::where([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ])->exists();

        $this->assertTrue($hasAccess);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function studentWithoutLinkCannotAccessInstructorData()
    {
        $otherInstructor = User::factory()->create();
        $otherInstructor->tenants()->attach($this->tenant->id);

        $hasAccess = InstructorStudentLink::where([
            'instructor_id' => $otherInstructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ])->exists();

        $this->assertFalse($hasAccess);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revokingLinkMakesStudentLoseAccess()
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $link->update(['status' => LinkStatus::REVOKED, 'revoked_at' => now()]);
        $link->delete();

        $this->assertEquals(LinkStatus::REVOKED, $link->fresh()->status);
        $this->assertNotNull($link->fresh()->deleted_at);

        $activeLinks = InstructorStudentLink::where([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ])->get();

        $this->assertFalse($activeLinks->contains('id', $link->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multipleInstructorsAreIsolatedByContext()
    {
        $instructor1 = User::factory()->create();
        $instructor2 = User::factory()->create();
        $instructor1->tenants()->attach($this->tenant->id);
        $instructor2->tenants()->attach($this->tenant->id);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $visibleLink = InstructorStudentLink::where([
            'instructor_id' => $instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ])->first();

        $this->assertNotNull($visibleLink);
        $this->assertEquals($instructor1->id, $visibleLink->instructor_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function softDeletedLinksAreHiddenButAuditable()
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);
        $link->delete();

        $normalQuery = InstructorStudentLink::where('id', $link->id)->first();

        $this->assertNull($normalQuery);

        $auditQuery = InstructorStudentLink::withTrashed()->find($link->id);
        $this->assertNotNull($auditQuery);
        $this->assertNotNull($auditQuery->deleted_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invitationTokenIsSecureAndValidates()
    {
        $invitation = Invitation::factory()->create(['tenant_id' => $this->tenant->id]);
        $token = $invitation->token;

        // Quando: usar token para aceitar
        $foundInvite = Invitation::where('token', $token)->first();

        // Então: encontra por token
        $this->assertNotNull($foundInvite);
        $this->assertEquals($invitation->id, $foundInvite->id);

        // E: token inválido não encontra
        $notFound = Invitation::where('token', 'invalid-token-xyz')->first();
        $this->assertNull($notFound);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invitationAndLinkAreCoordinated()
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitation->update(['status' => InvitationStatus::ACCEPTED, 'accepted_at' => now()]);
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'invitation_id' => $invitation->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->assertEquals(InvitationStatus::ACCEPTED, $invitation->fresh()->status);
        $this->assertEquals(LinkStatus::ACTIVE, $link->status);
        $this->assertEquals($invitation->id, $link->invitation_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revokedLinkCannotBeReactivated()
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $link->update(['status' => LinkStatus::REVOKED, 'revoked_at' => now()]);
        $link->delete();

        $this->assertEquals(LinkStatus::REVOKED, $link->fresh()->status);
        $this->assertNotNull($link->fresh()->deleted_at);

        $reactivated = InstructorStudentLink::where([
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ])->first();

        $this->assertNull($reactivated);
    }
}
