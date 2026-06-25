<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use App\Traits\ValidatesTenantAccess;

class InvitationPolicy
{
    use ValidatesTenantAccess;

    /**
     * Determine whether the user can view any invitations.
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
     * Determine whether the user can view the invitation.
     *
     * @param User $user
     * @param Invitation $invitation
     *
     * @return bool
     */
    public function view(User $user, Invitation $invitation): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $invitation)) {
            return false;
        }

        // Instructor can view their own invitations
        if ($invitation->instructor_id === $user->id) {
            return true;
        }

        // Student can view invitations sent to them
        if ($invitation->student_id === $user->id) {
            return true;
        }

        // User with matching email can view invitation
        if ($invitation->email === $user->email) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create invitations.
     *
     * @param User $user
     *
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the invitation.
     *
     * @param User $user
     * @param Invitation $invitation
     *
     * @return bool
     */
    public function update(User $user, Invitation $invitation): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $invitation)) {
            return false;
        }

        // Only instructor who created it can update
        if ($invitation->instructor_id !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the invitation.
     *
     * @param User $user
     * @param Invitation $invitation
     *
     * @return bool
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $invitation)) {
            return false;
        }

        // Only instructor who created it can delete
        if ($invitation->instructor_id !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can accept the invitation.
     *
     * @param User $user
     * @param Invitation $invitation
     *
     * @return bool
     */
    public function accept(User $user, Invitation $invitation): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $invitation)) {
            return false;
        }

        // Student (intended recipient) can accept
        if ($invitation->student_id === $user->id) {
            return true;
        }

        // User with matching email can accept
        if ($invitation->email === $user->email) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reject the invitation.
     *
     * @param User $user
     * @param Invitation $invitation
     *
     * @return bool
     */
    public function reject(User $user, Invitation $invitation): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $invitation)) {
            return false;
        }

        // Student (intended recipient) can reject
        if ($invitation->student_id === $user->id) {
            return true;
        }

        // User with matching email can reject
        if ($invitation->email === $user->email) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can resend the invitation.
     *
     * @param User $user
     * @param Invitation $invitation
     *
     * @return bool
     */
    public function resend(User $user, Invitation $invitation): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $invitation)) {
            return false;
        }

        // Only instructor who created it can resend
        if ($invitation->instructor_id !== $user->id) {
            return false;
        }

        return true;
    }
}
