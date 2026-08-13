<?php

namespace App\Services;

use App\Models\User;
use App\Support\NotificationSections;
use Illuminate\Notifications\Notification;

class NotificationCenter
{
    public function notifyAdmins(Notification $notification): void
    {
        User::query()
            ->active()
            ->whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->each(fn (User $admin) => $admin->notify($notification));
    }

    public function totalUnread(User $user): int
    {
        return (int) $user->unreadNotifications()->count();
    }

    /** @return array<string, int> */
    public function sectionCounts(User $user): array
    {
        $counts = array_fill_keys(NotificationSections::all(), 0);

        $user->unreadNotifications()
            ->get(['id', 'data'])
            ->each(function ($notification) use (&$counts) {
                $section = $notification->data['section'] ?? $this->guessSectionFromUrl($notification->data['url'] ?? '');
                if ($section && array_key_exists($section, $counts)) {
                    $counts[$section]++;
                }
            });

        return $counts;
    }

    public function markSectionRead(User $user, string $section): void
    {
        $user->unreadNotifications()
            ->get()
            ->each(function ($notification) use ($section) {
                $notificationSection = $notification->data['section']
                    ?? $this->guessSectionFromUrl($notification->data['url'] ?? '');

                if ($notificationSection === $section) {
                    $notification->markAsRead();
                }
            });
    }

    protected function guessSectionFromUrl(string $url): ?string
    {
        return match (true) {
            str_contains($url, 'families-trash') => NotificationSections::FAMILIES_TRASH,
            str_contains($url, 'families') => NotificationSections::FAMILIES,
            str_contains($url, 'camps') => NotificationSections::CAMPS,
            str_contains($url, 'aid') => NotificationSections::AID,
            str_contains($url, 'users') => NotificationSections::USERS,
            str_contains($url, 'roles') => NotificationSections::ROLES,
            str_contains($url, 'notifications') => NotificationSections::NOTIFICATIONS,
            default => null,
        };
    }
}
