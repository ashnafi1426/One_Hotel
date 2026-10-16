<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Guest;
use App\Services\AuthorizationService;

class GuestPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function viewAny(User $user): bool
    {
        return $this->authService->hasPermission($user, 'guests.view');
    }

    public function view(User $user, Guest $guest): bool
    {
        return $this->authService->hasPermission($user, 'guests.view');
    }

    public function create(User $user): bool
    {
        return $this->authService->hasPermission($user, 'guests.create');
    }

    public function update(User $user, Guest $guest): bool
    {
        return $this->authService->hasPermission($user, 'guests.update');
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $this->authService->hasPermission($user, 'guests.delete');
    }
}
