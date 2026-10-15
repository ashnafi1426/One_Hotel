<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payment;
use App\Services\AuthorizationService;
use App\Services\TenantContext;

class PaymentPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    protected function checkTenantAccess(User $user, ?string $hotelId = null): bool
    {
        return $user !== null;
    }

    public function viewAny(User $user): bool
    {
        return $this->checkTenantAccess($user, null)
            && $this->authService->hasPermission($user, 'payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->checkTenantAccess($user, $payment->hotel_id)
            && $this->authService->hasPermission($user, 'payments.view');
    }

    public function create(User $user): bool
    {
        return $this->checkTenantAccess($user, null)
            && $this->authService->hasPermission($user, 'payments.create');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $this->checkTenantAccess($user, $payment->hotel_id)
            && $this->authService->hasPermission($user, 'payments.refund');
    }

    public function approveRefund(User $user, Payment $payment): bool
    {
        return $this->checkTenantAccess($user, $payment->hotel_id)
            && $this->authService->hasPermission($user, 'payments.approve_refund');
    }
}

