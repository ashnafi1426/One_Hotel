<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MenuItem;
use App\Services\AuthorizationService;

class MenuItemPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function viewAny(User $user): bool
    {
        return $this->authService->hasPermission($user, 'menu.view');
    }

    public function view(User $user, MenuItem $item): bool
    {
        return $this->authService->hasPermission($user, 'menu.view');
    }

    public function create(User $user): bool
    {
        return $this->authService->hasPermission($user, 'menu.create');
    }

    public function update(User $user, MenuItem $item): bool
    {
        return $this->authService->hasPermission($user, 'menu.update');
    }

    public function delete(User $user, MenuItem $item): bool
    {
        return $this->authService->hasPermission($user, 'menu.delete');
    }
}
