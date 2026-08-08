<?php

namespace App\Notifications;

use App\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Approval $approval,
        public readonly string $decision, // 'approved' | 'rejected'
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $person = $this->approval->approvable;
        $name = $person?->display_name ?? 'Data';
        $decider = $this->approval->approver?->name ?? 'Admin';

        $actionLabel = match ($this->approval->action) {
            'create' => 'penambahan',
            'update' => 'perubahan',
            'delete' => 'penghapusan',
            default => $this->approval->action,
        };

        $statusLabel = $this->decision === 'approved' ? 'disetujui' : 'ditolak';

        return [
            'type' => 'approval_decided',
            'approval_id' => $this->approval->id,
            'decision' => $this->decision,
            'action' => $this->approval->action,
            'person_name' => $name,
            'decider' => $decider,
            'reason' => $this->reason,
            'message' => "Permintaan {$actionLabel} \"{$name}\" {$statusLabel} oleh {$decider}",
            'url' => "/approvals/{$this->approval->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $person = $this->approval->approvable;
        $name = $person?->display_name ?? 'Data';
        $decider = $this->approval->approver?->name ?? 'Admin';
        $approved = $this->decision === 'approved';

        $mail = (new MailMessage)
            ->subject(($approved ? '✅ Disetujui' : '❌ Ditolak')." — {$name}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line('Permintaan '.match ($this->approval->action) {
                'create' => 'penambahan',
                'update' => 'perubahan',
                'delete' => 'penghapusan',
                default => $this->approval->action,
            }." data \"{$name}\" telah ".($approved ? 'disetujui' : 'ditolak')." oleh {$decider}.");

        if (! $approved && $this->reason) {
            $mail->line("**Alasan penolakan:** {$this->reason}");
        }

        return $mail
            ->action('Lihat Detail', url("/approvals/{$this->approval->id}"));
    }
}
