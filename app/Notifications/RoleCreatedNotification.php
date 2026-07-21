<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoleCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $roleName, public ?string $displayName = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $display = $this->displayName ?: $this->roleName;

        return [
            'title' => 'دور جديد',
            'message' => "تم إنشاء دور جديد: {$display}",
            'icon' => 'fa-shield-alt',
            'url' => route('roles.index'),
            'role_name' => $this->roleName,
            'display_name' => $this->displayName,
        ];
    }
}
