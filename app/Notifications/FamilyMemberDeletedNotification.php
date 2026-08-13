<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FamilyMemberDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $memberName, public ?string $familyName = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'حذف فرد من عائلة',
            'message' => "تم حذف الفرد \"{$this->memberName}\"" . ($this->familyName ? " من عائلة {$this->familyName}" : ''),
            'icon'    => 'fa-user-minus',
            'url'     => route('families.index'),
            'section' => NotificationSections::FAMILIES,
        ];
    }
}
