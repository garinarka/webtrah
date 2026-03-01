<?php

namespace App\Notifications;

use App\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovalRequested extends Notification
{
    use Queueable;

    public function __construct(public readonly Approval $approval) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $person   = $this->approval->approvable;
        $name     = $person?->display_name ?? 'Data baru';
        $requester = $this->approval->requester?->name ?? 'Seseorang';

        $actionLabel = match ($this->approval->action) {
            'create' => 'penambahan',
            'update' => 'perubahan',
            'delete' => 'penghapusan',
            default  => $this->approval->action,
        };

        return [
            'type'         => 'approval_requested',
            'approval_id'  => $this->approval->id,
            'action'       => $this->approval->action,
            'person_name'  => $name,
            'requester'    => $requester,
            'message'      => "{$requester} mengajukan {$actionLabel} data \"{$name}\"",
            'url'          => "/approvals/{$this->approval->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $person    = $this->approval->approvable;
        $name      = $person?->display_name ?? 'Data baru';
        $requester = $this->approval->requester?->name ?? 'Seseorang';

        return (new MailMessage)
            ->subject("Permintaan Persetujuan Baru — {$name}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("{$requester} mengajukan permintaan " . match ($this->approval->action) {
                'create' => 'penambahan',
                'update' => 'perubahan',
                'delete' => 'penghapusan',
                default  => $this->approval->action,
            } . " data \"{$name}\".")
            ->action('Tinjau Permintaan', url("/approvals/{$this->approval->id}"))
            ->line('Silakan tinjau dan berikan keputusan Anda.');
    }
}
