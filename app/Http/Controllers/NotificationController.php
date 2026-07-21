<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $unreadCount = Auth::user()
            ->unreadNotifications()
            ->count();

        return response()->json([
            'notifications' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? 'إشعار',
                    'message' => $n->data['message'] ?? '',
                    'icon' => $n->data['icon'] ?? 'fa-bell',
                    'url' => $n->data['url'] ?? '#',
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                ];
            }),
            'unread_count' => $unreadCount,
        ]);
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
}
