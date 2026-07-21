<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampCreatedNotification extends Notification
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
            'title' => 'مخيم جديد',
            'message' => "تم إنشاء مخيم جديد: {$this->campName}" . ($this->location ? " ({$this->location})" : ''),
            'icon' => 'fa-campground',
            'url' => route('camps.index'),
            'camp_name' => $this->campName,
            'location' => $this->location,
        ];
    }
}
