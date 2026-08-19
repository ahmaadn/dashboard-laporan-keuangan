<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->manage($user);
    }

    public function adjustStock(User $user, Product $product): bool
    {
        return $this->manage($user);
    }

    public function manage(User $user): bool
    {
        return in_array($user->peran, ['admin', 'pegawai'], true);
    }
}
