<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Traits\ValidatesTenantAccess;

class ClientPolicy
{
    use ValidatesTenantAccess;
    public function viewAny(User $user): bool
    {
        if ($user->can('client.view_any')) {
            return true;
        }

        return $user->can('client.view');
    }

    public function view(User $user, Client $client): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $client)) {
            return false;
        }

        // Check permission
        $canView = $user->can('client.view');

        if (!$canView) {
            return false;
        }

        // Verify ownership for customers
        if ($user->hasRole('customer')) {
            return $client->isUserCustomer($user);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('client.create');
    }

    public function update(User $user, Client $client): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $client)) {
            return false;
        }

        return $user->can('client.update');
    }

    public function delete(User $user, Client $client): bool
    {
        // Validate tenant access first
        if (!$this->userCanAccessTenantResource($user, $client)) {
            return false;
        }

        return $user->can('client.delete');
    }
}
