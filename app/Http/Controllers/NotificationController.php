<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function createNotification(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string|max:255',
            'user_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'is_read' => 'boolean',
        ]);

        $notification = Notification::create([
            'title' => $validatedData['title'],
            'body' => $validatedData['body'],
            'user_id' => $validatedData['user_id'],
            'receiver_id' => $validatedData['receiver_id'],
            'booking_id' => $validatedData['booking_id'] ?? null,
            'is_read' => $validatedData['is_read'] ?? false,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification created successfully',
            'notification' => $notification,
        ], 201);
    }

    public function getNotifications(Request $request): JsonResponse
    {
        $query = Notification::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('receiver_id')) {
            $query->where('receiver_id', $request->receiver_id);
        }

        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'notifications' => $notifications,
        ], 200);
    }

    public function markNotificationAsRead(Request $request, $id): JsonResponse
    {
        $notification = Notification::find($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notification marked as read successfully',
            'notification' => $notification,
        ], 200);
    }
}
