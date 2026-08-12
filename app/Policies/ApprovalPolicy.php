<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * siapa yang boleh lihat detail approval:
     * - admin: semua approval
     * - selain admin (moderator/user): HANYA approval yang mereka ajukan
     *   sendiri (requested_by = mereka) — tidak boleh intip approval
     *   orang lain walau tau ID-nya langsung dari URL
     */
    public function view(User $user, Approval $approval): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $approval->requested_by === (int) $user->id;
    }

    public function approve(User $user, Approval $approval): bool
    {
        // Hanya admin yang boleh approve/reject — moderator cuma bisa
        // MENGAJUKAN perubahan, bukan menyetujuinya sendiri.
        return $user->isAdmin();
    }

    public function reject(User $user, Approval $approval): bool
    {
        return $this->approve($user, $approval);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Approval $approval): bool
    {
        return false;
    }

    public function delete(User $user, Approval $approval): bool
    {
        return false;
    }

    public function restore(User $user, Approval $approval): bool
    {
        return false;
    }

    public function forceDelete(User $user, Approval $approval): bool
    {
        return false;
    }
}
