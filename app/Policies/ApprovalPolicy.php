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
     * - admin / moderator yang punya permission view_approvals
     * - requester itu sendiri (agar bisa buka link dari notifikasi)
     */
    public function view(User $user, Approval $approval): bool
    {
        if ($user->can('view_approvals')) {
            return true;
        }

        // requester boleh lihat approval miliknya sendiri
        // (misal: user/moderator klik notif "ajuanmu disetujui")
        return (int) $approval->requested_by === (int) $user->id;
    }

    public function approve(User $user, Approval $approval): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isModerator()) {
            if (! $user->can('approve_changes')) {
                return false;
            }

            // Resign moderator: approvable null — hanya admin yang bisa approve
            if ($approval->action === 'resign_moderator') {
                return false;
            }

            // ⚠️  approvable bisa NULL jika person sudah dihapus
            // (misal: approval "delete" sudah disetujui → person terhapus)
            $approvable = $approval->approvable;
            if (is_null($approvable)) {
                // approval sudah selesai, tidak ada lagi yang bisa di-approve
                return false;
            }

            // Gunakan managesUnit() yang sudah menggunakan raw DB query
            // agar tidak ada bug kolom 'family_unit_id' yang sudah dihapus dari tabel users
            return $user->managesUnit($approvable->family_unit_id);
        }

        return false;
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
