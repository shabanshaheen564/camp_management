<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AidUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $campName, public ?string $aidType = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'تعديل توزيع مساعدات',
            'message' => 'تم تعديل توزيع مساعدات' . ($this->aidType ? " ({$this->aidType})" : '') . " — مخيم {$this->campName}",
            'icon'    => 'fa-box-open',
            'url'     => route('aid.index'),
            'section' => NotificationSections::AID,
        ];
    }
}
