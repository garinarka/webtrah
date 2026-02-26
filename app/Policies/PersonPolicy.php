<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
// use Illuminate\Auth\Access\Response;

class PersonPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Person $person): bool
    {
        return $user->family_unit_id === $person->family_unit_id
            || $user->can('view_people');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_person');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Person $person): bool
    {
        if ($user->can('edit_any_person')) return true;
        if ($person->created_by === $user->id) return true;
        return $user->can('edit_person') && $user->family_unit_id === $person->family_unit_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Person $person): bool
    {
        return $user->can('delete_person');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Person $person): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Person $person): bool
    {
        return false;
    }
}
