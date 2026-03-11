<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($payment->vendor);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->hasRole('super_admin');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function markAsPaid(User $user, Payment $payment): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
