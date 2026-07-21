<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FamilyUpdatedNotification extends Notification
{
    use Queueable;

    protected string $familyName;
    protected ?string $campName;
    protected ?string $cardId;

    public function __construct(string $familyName, ?string $campName = null, ?string $cardId = null)
    {
        $this->familyName = $familyName;
        $this->campName   = $campName;
        $this->cardId     = $cardId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title'   => 'تعديل بيانات عائلة',
            'message' => "تم تعديل بيانات عائلة \"{$this->familyName}\"" . ($this->campName ? " - مخيم {$this->campName}" : ''),
            'icon'    => 'fa-users',
            'url'     => route('families.index'),
        ];
    }
}
