<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        // Cannot delete self.
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete the last active admin (BR-4).
        if ($model->isAdmin() && $model->is_active && ! $model->trashed()) {
            $activeAdmins = User::where('peran', 'admin')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $model->id)
                ->count();

            if ($activeAdmins === 0) {
                return false;
            }
        }

        return $user->isAdmin();
    }
}
