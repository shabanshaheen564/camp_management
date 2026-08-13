<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FamilyRestoredNotification extends Notification
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
            'title'   => 'استرجاع عائلة',
            'message' => "تم استرجاع عائلة \"{$this->familyName}\"" . ($this->campName ? " إلى مخيم {$this->campName}" : ''),
            'icon'    => 'fa-undo',
            'url'     => route('families.index'),
            'section' => NotificationSections::FAMILIES,
        ];
    }
}
