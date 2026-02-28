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
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * admin: siapapun bisa diedit
     * moderator: bisa edit siapapun dalam family unit
     * user: hanya bisa edit data yang dia sendiri buat
     */
    public function update(User $user, Person $person): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isModerator()) return true;
        // user biasa: hanya record yang dia buat sendiri
        return $person->created_by === $user->id;
    }

    /**
     * admin: bisa hapus langsung
     * moderator: bisa ajukan penghapusan (perlu approval admin)
     * user: tidak bisa hapus sama sekali
     */
    public function delete(User $user, Person $person): bool
    {
        return $user->isAdmin() || $user->isModerator();
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
