<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, Subscription $subscription): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($subscription->vendor);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Subscription $subscription): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return $user->hasRole('super_admin');
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($subscription->vendor) && 
               $user->getVendorRole($subscription->vendor) === 'owner';
    }

    public function changePlan(User $user, Subscription $subscription): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($subscription->vendor) && 
               $user->getVendorRole($subscription->vendor) === 'owner';
    }
}
