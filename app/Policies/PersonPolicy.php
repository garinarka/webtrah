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

    /**
     * User TIDAK bisa membuat data baru.
     * Hanya admin dan moderator yang bisa tambah anggota.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * User hanya bisa edit data yang DIA sendiri buat (via approval queue).
     * Admin dan moderator bisa edit semua.
     */
    public function update(User $user, Person $person): bool
    {
        if ($user->isAdmin())     return true;
        if ($user->isModerator()) return true;
        // User biasa: hanya data yang dia buat sendiri
        return $person->created_by === $user->id;
    }

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
