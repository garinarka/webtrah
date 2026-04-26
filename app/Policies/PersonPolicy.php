<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_people');
    }

    public function view(User $user, Person $person): bool
    {
        return $user->can('view_people');
    }

    public function create(User $user): bool
    {
        // Gunakan permission 'create_person' agar konsisten dengan sistem RBAC Spatie.
        // Admin & Moderator punya permission ini (seeder), User biasa tidak.
        // Ini lebih clean daripada hardcode isAdmin || isModerator.
        return $user->can('create_person');
    }

    public function update(User $user, Person $person): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isModerator()) {
            return $user->managesUnit($person->family_unit_id);
        }

        return $person->created_by === $user->id;
    }

    public function delete(User $user, Person $person): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isModerator()) {
            return $user->managesUnit($person->family_unit_id);
        }

        return false;
    }

    public function restore(User $user, Person $person): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Person $person): bool
    {
        return $user->isAdmin();
    }
}
