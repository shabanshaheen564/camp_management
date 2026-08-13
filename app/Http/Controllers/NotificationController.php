<?php

namespace App\Http\Controllers;

use App\Support\NotificationSections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->ajax() && ! $request->wantsJson()) {
            $this->markNotificationsRead(NotificationSections::NOTIFICATIONS);
        }

        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(20);
        $center = app(\App\Services\NotificationCenter::class);
        $unreadCount = $center->totalUnread($user);
        $sectionCounts = $center->sectionCounts($user);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'notifications' => $notifications->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'title' => $n->data['title'] ?? 'إشعار',
                        'message' => $n->data['message'] ?? '',
                        'icon' => $n->data['icon'] ?? 'fa-bell',
                        'url' => $n->data['url'] ?? '#',
                        'section' => $n->data['section'] ?? null,
                        'read_at' => $n->read_at,
                        'created_at' => $n->created_at,
                    ];
                }),
                'unread_count' => $unreadCount,
                'section_counts' => $sectionCounts,
            ]);
        }

        return view('camp_management.notifications', compact('notifications', 'unreadCount', 'sectionCounts'));
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        Auth::user()
            ->unreadNotifications()
            ->get()
            ->each->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markSectionRead(Request $request, string $section)
    {
        if (! in_array($section, NotificationSections::all(), true)) {
            abort(404);
        }

        app(\App\Services\NotificationCenter::class)->markSectionRead(Auth::user(), $section);

        return response()->json(['success' => true]);
    }
}
