<?php

namespace App\Policies;

use App\Models\FamilyUnit;
use App\Models\User;

class FamilyUnitPolicy
{
    /** Semua user login bisa lihat daftar unit keluarga */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FamilyUnit $familyUnit): bool
    {
        return true;
    }

    /** Hanya admin yang bisa buat unit keluarga */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Hanya admin yang bisa edit unit keluarga */
    public function update(User $user, FamilyUnit $familyUnit): bool
    {
        return $user->isAdmin();
    }

    /** Hanya admin yang bisa hapus unit keluarga */
    public function delete(User $user, FamilyUnit $familyUnit): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, FamilyUnit $familyUnit): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, FamilyUnit $familyUnit): bool
    {
        return $user->isAdmin();
    }
}
