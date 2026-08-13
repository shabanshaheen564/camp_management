<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RoleUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $roleName,
        public ?string $displayName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $display = $this->displayName ?: $this->roleName;

        return [
            'title' => 'تعديل دور',
            'message' => "تم تعديل الدور \"{$display}\"",
            'icon' => 'fa-shield-alt',
            'url' => route('roles.index'),
            'section' => NotificationSections::ROLES,
            'role_name' => $this->roleName,
            'display_name' => $this->displayName,
        ];
    }
}
