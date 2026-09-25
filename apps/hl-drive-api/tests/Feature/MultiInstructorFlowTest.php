<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\LinkStatus;
use App\Services\TenantResolver;
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
        $tenantResolver = app(TenantResolver::class);
        $tenantResolver->setTenantId($this->tenant->id);

        $this->instructor1 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->instructor2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->student = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function testStudentCanManageLinksWithMultipleInstructors(): void
    {
        $link1 = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $link2 = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $studentLinks = InstructorStudentLink::where('student_id', $this->student->id)
            ->active()
            ->get();

        $this->assertCount(2, $studentLinks);
        $this->assertTrue($studentLinks->contains('instructor_id', $this->instructor1->id));
        $this->assertTrue($studentLinks->contains('instructor_id', $this->instructor2->id));
    }

    public function testStudentCanSwitchActiveInstructor(): void
    {
        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor2->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->student->update(['active_instructor_id' => $this->instructor2->id]);
        $this->student->refresh();

        $this->assertEquals($this->student->active_instructor_id, $this->instructor2->id);

        $this->student->update(['active_instructor_id' => $this->instructor1->id]);
        $this->student->refresh();

        $this->assertEquals($this->student->active_instructor_id, $this->instructor1->id);
    }

    public function testInstructorSeesOnlyOwnStudentLinks(): void
    {
        $link1 = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $otherStudent = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $link2 = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor2->id,
            'student_id' => $otherStudent->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $inst1Links = InstructorStudentLink::byInstructor($this->instructor1->id)
            ->active()
            ->get();

        $this->assertCount(1, $inst1Links);
        $this->assertEquals($inst1Links->first()->instructor_id, $this->instructor1->id);

        $inst2Links = InstructorStudentLink::byInstructor($this->instructor2->id)
            ->active()
            ->get();

        $this->assertCount(1, $inst2Links);
        $this->assertEquals($inst2Links->first()->instructor_id, $this->instructor2->id);
    }

    public function testRevokingLinkRemovesInstructorAccess(): void
    {
        $link = InstructorStudentLink::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor1->id,
            'student_id' => $this->student->id,
            'status' => LinkStatus::ACTIVE,
        ]);

        $this->assertCount(1, InstructorStudentLink::where('student_id', $this->student->id)->active()->get());

        $link->update(['status' => LinkStatus::REVOKED]);

        $this->assertCount(0, InstructorStudentLink::where('student_id', $this->student->id)->active()->get());

        $this->assertCount(1, InstructorStudentLink::where('student_id', $this->student->id)->get());
    }
}
