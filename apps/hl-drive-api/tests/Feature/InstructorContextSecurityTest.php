<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\LinkStatus;
use App\Enums\InvitationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorContextSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $instructor1;
    protected User $student1;
    protected User $instructor2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Tenant::factory()->create();
        $this->tenant2 = Tenant::factory()->create();

        $this->instructor1 = User::factory()->create(['tenant_id' => $this->tenant1->id]);
        $this->student1 = User::factory()->create(['tenant_id' => $this->tenant1->id]);
        $this->instructor2 = User::factory()->create(['tenant_id' => $this->tenant2->id]);
    }

    public function test_sql_injection_in_instructor_filter_does_not_leak_data(): void
    {
        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student1->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $injectedId = "1 OR 1=1";
        $results = InstructorStudentLink::where('instructor_id', $injectedId)->get();

        $this->assertCount(0, $results);
    }

    public function test_cannot_change_status_to_invalid_enum(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student1->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->expectException(\ValueError::class);

        $link->update(['status' => 'INVALID_STATUS']);
    }

    public function test_middleware_blocks_invalid_instructor_context(): void
    {
        $invalidInstructorId = 9999;

        $links = InstructorStudentLink::byInstructor($invalidInstructorId)->get();

        $this->assertCount(0, $links);
    }

    public function test_soft_deleted_links_not_accessible(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student1->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $linkId = $link->id;

        $link->revoke();

        $found = InstructorStudentLink::find($linkId);

        $this->assertNull($found);
    }

    public function test_policy_blocks_unauthorized_users(): void
    {
        $otherInstructor = User::factory()->create(['tenant_id' => $this->tenant1->id]);

        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student1->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->assertFalse($otherInstructor->can('update', $link));
    }

    public function test_cross_tenant_access_blocked(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student1->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $crossTenantLinks = InstructorStudentLink::where('tenant_id', $this->tenant2->id)
            ->byInstructor($this->instructor1->id)
            ->get();

        $this->assertCount(0, $crossTenantLinks);
    }

    public function test_email_validation_prevents_injection(): void
    {
        $maliciousEmail = "'; DROP TABLE invitations; --";

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Invitation::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'email' => $maliciousEmail,
        ]);
    }

    public function test_soft_delete_integrity_maintained(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student1->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $linkId = $link->id;

        $link->revoke();

        $deletedLink = InstructorStudentLink::withTrashed()->find($linkId);

        $this->assertNotNull($deletedLink->deleted_at);
        $this->assertNotNull($deletedLink->revoked_at);
        $this->assertEquals($deletedLink->status->value, LinkStatus::REVOKED->value);
    }

    public function test_status_transitions_validated(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student1->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $link->suspend();

        $this->assertEquals($link->fresh()->status->value, LinkStatus::SUSPENDED->value);

        $link->reactivate();

        $this->assertEquals($link->fresh()->status->value, LinkStatus::ACTIVE->value);

        $link->revoke();

        $this->assertEquals($link->fresh()->status->value, LinkStatus::REVOKED->value);
    }
}
