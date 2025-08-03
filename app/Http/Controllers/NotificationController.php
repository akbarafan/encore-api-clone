<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->select('id', 'title', 'message', 'action_type', 'action_data', 'read_at', 'created_at')
            ->get();

        $unreadCount = $notifications->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'total' => $notifications->count()
            ]
        ]);
    }

    /**
     * Create new notification
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_type' => 'nullable|string',
            'action_data' => 'nullable|array',
        ]);

        $notification = Notification::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'message' => $request->message,
            'action_type' => $request->action_type,
            'action_data' => $request->action_data,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification
        ], 201);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($notification->read_at) {
            return response()->json([
                'success' => false,
                'message' => 'Notification already marked as read'
            ], 400);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $updated = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$updated} notifications as read"
        ]);
    }
}
