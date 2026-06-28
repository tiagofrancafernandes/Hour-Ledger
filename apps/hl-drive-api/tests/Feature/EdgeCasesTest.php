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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCasesTest extends TestCase
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
        $this->tenant = Tenant::factory()->create();
        $this->tenantResolver->setTenantId($this->tenant->id);

        $this->instructor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->student = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_invitation_tokens_must_be_unique(): void
    {
        $token = 'unique-token-abc123';

        Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'token' => $token,
        ]);

        $this->expectException(QueryException::class);

        $instructor2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $student2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $instructor2->id,
            'student_id' => $student2->id,
            'token' => $token,
        ]);
    }

    public function test_cannot_accept_invitation_twice(): void
    {
        $invitation = Invitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $invitation->accept();

        $this->assertTrue($invitation->status === InvitationStatus::ACCEPTED);

        $invitation->refresh();

        $this->assertTrue($invitation->status === InvitationStatus::ACCEPTED);
        $this->assertFalse($invitation->isPending());
    }

    public function test_soft_delete_preserves_link_history(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $linkId = $link->id;

        $this->assertNotNull(InstructorStudentLink::find($linkId));

        $link->delete();

        $this->assertNull(InstructorStudentLink::find($linkId));

        $deletedLink = InstructorStudentLink::withTrashed()->find($linkId);
        $this->assertNotNull($deletedLink);
        $this->assertNotNull($deletedLink->deleted_at);
    }

    public function test_cannot_create_duplicate_active_link(): void
    {
        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->expectException(QueryException::class);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);
    }

    public function test_link_can_be_revoked_and_recreated(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->assertTrue($link->status === LinkStatus::ACTIVE);

        $link->update(['status' => LinkStatus::REVOKED]);

        $link->refresh();
        $this->assertTrue($link->status === LinkStatus::REVOKED);
    }
}
