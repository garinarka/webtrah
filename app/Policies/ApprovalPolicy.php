<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;
// use Illuminate\Auth\Access\Response;

class ApprovalPolicy
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
    public function view(User $user, Approval $approval): bool
    {
        return $user->can('view_approvals');
    }

    public function approve(User $user, Approval $approval): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isModerator()) {
            if (!$user->can('approve_changes')) {
                return false;
            }

            $approvable = $approval->approvable;
            return $user->family_unit_id === $approvable->family_unit_id;
        }

        return false;
    }

    public function reject(User $user, Approval $approval): bool
    {
        return $this->approve($user, $approval);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Approval $approval): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Approval $approval): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Approval $approval): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Approval $approval): bool
    {
        return false;
    }
}
