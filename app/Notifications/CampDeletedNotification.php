<?php

namespace App\Notifications;

use App\Support\NotificationSections;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CampDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $campName, public ?string $location = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'حذف مخيم',
            'message' => "تم حذف المخيم \"{$this->campName}\"" . ($this->location ? " ({$this->location})" : ''),
            'icon'    => 'fa-trash',
            'url'     => route('camps.index'),
            'section' => NotificationSections::CAMPS,
        ];
    }
}
