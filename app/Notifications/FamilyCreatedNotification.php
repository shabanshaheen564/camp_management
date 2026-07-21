<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $guardianName, public ?string $campName = null, public ?string $cardId = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'عائلة جديدة',
            'message' => "تم تسجيل عائلة جديدة: {$this->guardianName}" . ($this->campName ? " في مخيم {$this->campName}" : ''),
            'icon' => 'fa-users',
            'url' => route('families.index'),
            'guardian_name' => $this->guardianName,
            'camp_name' => $this->campName,
            'card_id' => $this->cardId,
        ];
    }
}
