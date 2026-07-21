<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FamilyMemberAddedNotification extends Notification
{
    use Queueable;

    protected string $memberName;
    protected ?string $familyName;
    protected ?string $campName;

    public function __construct(string $memberName, ?string $familyName = null, ?string $campName = null)
    {
        $this->memberName = $memberName;
        $this->familyName = $familyName;
        $this->campName   = $campName;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title'   => 'إضافة فرد جديد',
            'message' => "تمت إضافة \"{$this->memberName}\" إلى عائلة \"{$this->familyName}\"" . ($this->campName ? " - مخيم {$this->campName}" : ''),
            'icon'    => 'fa-user-plus',
            'url'     => route('families.index'),
        ];
    }
}
