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
            if (! $subjectUnit || ! $objectUnit) {
                return false;
            }

            return $user->managesUnit($subjectUnit) && $user->managesUnit($objectUnit);
        }

        return false;
    }
}
