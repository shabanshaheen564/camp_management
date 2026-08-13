<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FamilyForceDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $familyName, public ?string $campName = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'حذف نهائي لعائلة',
            'message' => "تم الحذف النهائي لعائلة \"{$this->familyName}\"" . ($this->campName ? " ({$this->campName})" : ''),
            'icon'    => 'fa-trash-alt',
            'url'     => route('families.trash'),
            'section' => NotificationSections::FAMILIES_TRASH,
        ];
    }
}
