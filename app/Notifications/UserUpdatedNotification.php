<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $userName,
        public ?string $roleName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تعديل مستخدم',
            'message' => "تم تعديل بيانات المستخدم \"{$this->userName}\"" . ($this->roleName ? " — {$this->roleName}" : ''),
            'icon' => 'fa-user-edit',
            'url' => route('users.index'),
            'section' => NotificationSections::USERS,
            'user_name' => $this->userName,
            'role_name' => $this->roleName,
        ];
    }
}
