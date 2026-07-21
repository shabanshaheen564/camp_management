<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CampUpdatedNotification extends Notification
{
    use Queueable;

    protected string $campName;
    protected ?string $location;

    public function __construct(string $campName, ?string $location = null)
    {
        $this->campName = $campName;
        $this->location = $location;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title'   => 'تعديل بيانات مخيم',
            'message' => "تم تعديل بيانات مخيم \"{$this->campName}\"" . ($this->location ? " - {$this->location}" : ''),
            'icon'    => 'fa-tent',
            'url'     => route('camps.index'),
        ];
    }
}
