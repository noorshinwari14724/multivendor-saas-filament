<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, Vendor $vendor): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($vendor);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, Vendor $vendor): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($vendor) && 
               in_array($user->getVendorRole($vendor), ['owner', 'admin']);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Vendor $vendor): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Vendor $vendor): bool
    {
        return $user->hasRole('super_admin');
    }

    public function approve(User $user, Vendor $vendor): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function reject(User $user, Vendor $vendor): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function suspend(User $user, Vendor $vendor): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function manageSettings(User $user, Vendor $vendor): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($vendor) && 
               in_array($user->getVendorRole($vendor), ['owner', 'admin']);
    }

    public function manageUsers(User $user, Vendor $vendor): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($vendor) && 
               in_array($user->getVendorRole($vendor), ['owner', 'admin', 'manager']);
    }

    public function manageBilling(User $user, Vendor $vendor): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->belongsToVendor($vendor) && 
               $user->getVendorRole($vendor) === 'owner';
    }
}
