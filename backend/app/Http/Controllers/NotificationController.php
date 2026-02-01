<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()
            ->orderByRaw('read_at IS NULL DESC')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(function ($notification) {
                return [
                    'id' => $notification->id,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'data' => $notification->data,
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }
        Cache::forget('notifications:unread_count:'.$request->user()->id);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        Cache::forget('notifications:unread_count:'.$request->user()->id);

        return response()->json(['success' => true]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $key = 'notifications:unread_count:'.$request->user()->id;

        return response()->json([
            'unread_count' => Cache::remember(
                $key,
                now()->addMinutes(5),
                fn () => $request->user()->unreadNotifications()->count()
            ),
        ]);
    }
}
