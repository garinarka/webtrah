<?php

namespace App\Policies;

use App\Models\FamilyUnit;
use App\Models\User;

class FamilyUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FamilyUnit $familyUnit): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admin: semua unit.
     * Moderator: hanya unit yang mereka naungi (via pivot).
     */
    public function update(User $user, FamilyUnit $familyUnit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isModerator()) {
            return $user->managesUnit($familyUnit->id);
        }

        return false;
    }

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
