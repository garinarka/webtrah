<?php

namespace App\Policies;

use App\Models\Relationship;
use App\Models\User;

class RelationshipPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    public function approve(User $user, Relationship $relationship): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Relationship $relationship): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isModerator()) {
            $subjectUnit = $relationship->subject?->family_unit_id;
            $objectUnit = $relationship->object?->family_unit_id;

            // Jika salah satu person tidak punya unit, hanya admin yang bisa hapus
            if (! $subjectUnit && ! $objectUnit) {
                return false;
            }

            // Cukup mengelola SALAH SATU dari kedua unit yang terlibat.
            // Syarat "harus kelola keduanya" terlalu ketat — jika subject & object
            // berasal dari unit berbeda, tidak ada moderator tunggal yang bisa memenuhinya.
            return $user->managesUnit($subjectUnit) || $user->managesUnit($objectUnit);
        }

        return false;
    }
}
