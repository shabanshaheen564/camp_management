<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $userName, public ?string $roleName = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'مستخدم جديد',
            'message' => "تم إنشاء مستخدم جديد: {$this->userName}" . ($this->roleName ? " بدور {$this->roleName}" : ''),
            'icon' => 'fa-user-plus',
            'url' => route('users.index'),
            'user_name' => $this->userName,
            'role_name' => $this->roleName,
        ];
    }
}
