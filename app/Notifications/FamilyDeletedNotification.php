<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FamilyDeletedNotification extends Notification
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
            'title'   => 'نقل عائلة إلى سلة المحذوفات',
            'message' => "تم حذف عائلة \"{$this->familyName}\"" . ($this->campName ? " من مخيم {$this->campName}" : '') . ' — راجع سلة المحذوفات',
            'icon'    => 'fa-trash',
            'url'     => route('families.trash'),
            'section' => NotificationSections::FAMILIES_TRASH,
        ];
    }
}
