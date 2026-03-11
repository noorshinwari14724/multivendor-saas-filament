<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Plan $plan): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Plan $plan): bool
    {
        return $user->hasRole('super_admin');
    }
}
