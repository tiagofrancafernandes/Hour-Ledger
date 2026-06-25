<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InstructorStudentLink;
use App\Models\User;
use App\Traits\ValidatesTenantAccess;

class InstructorStudentLinkPolicy
{
    use ValidatesTenantAccess;

    /**
     * Determine whether the user can view any links.
     *
     * @param User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the link.
     *
     * @param User $user
     * @param InstructorStudentLink $link
     *
     * @return bool
     */
    public function view(User $user, InstructorStudentLink $link): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $link)) {
            return false;
        }

        // Instructor can view their links with students
        if ($link->instructor_id === $user->id) {
            return true;
        }

        // Student can view their links with instructors
        if ($link->student_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete/revoke the link.
     *
     * @param User $user
     * @param InstructorStudentLink $link
     *
     * @return bool
     */
    public function delete(User $user, InstructorStudentLink $link): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $link)) {
            return false;
        }

        // Instructor can revoke link with student
        if ($link->instructor_id === $user->id) {
            return true;
        }

        // Student can revoke link with instructor
        if ($link->student_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can switch active instructor.
     *
     * @param User $user
     *
     * @return bool
     */
    public function switchInstructor(User $user): bool
    {
        return true;
    }
}
