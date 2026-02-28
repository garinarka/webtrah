<?php

namespace App\Policies;

use App\Models\Relationship;
use App\Models\User;

class RelationshipPolicy
{
    /** admin & moderator bisa membuat relasi */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * admin: langsung approve.
     * moderator: bisa buat tapi status pending.
     */
    public function approve(User $user, Relationship $relationship): bool
    {
        return $user->isAdmin();
    }

    /** admin bisa hapus langsung. moderator bisa ajukan hapus. */
    public function delete(User $user, Relationship $relationship): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
