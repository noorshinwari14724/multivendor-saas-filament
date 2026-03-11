<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        return $user->hasRole('super_admin') || 
               ($user->hasRole('admin') && !$model->hasRole('admin'));
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('super_admin');
    }

    public function manageRoles(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        return $user->hasRole('super_admin') || 
               ($user->hasRole('admin') && !$model->hasRole('admin'));
    }

    public function suspend(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        return $user->hasRole(['super_admin', 'admin']);
    }

    public function impersonate(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        return $user->hasRole(['super_admin', 'admin']);
    }
}
