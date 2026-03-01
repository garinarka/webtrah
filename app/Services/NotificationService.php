<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\User;
use App\Notifications\ApprovalRequested;

class NotificationService
{
    /**
     * kirim notifikasi ke semua admin bahwa ada approval baru yang perlu ditinjau
     */
    public static function notifyAdminsOfNewApproval(Approval $approval): void
    {
        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            // jangan notif ke diri sendiri jika admin yang membuat
            if ($admin->id === $approval->requested_by) continue;

            $admin->notify(new ApprovalRequested($approval));
        }
    }
}
