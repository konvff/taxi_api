<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function index()
    {
        $users = User::all();

        return response()->json(['users' => $users], 200);
    }

    // Get single user by ID
    public function show($id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $user], 200);
    }

    // Update user
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'email' => 'nullable',
            'phone' => 'string|max:255',
            'category' => 'nullable|string|max:255',
            'location' => 'string|max:255',
            'role' => 'string|max:255',
            'car_name' => 'nullable|string|max:255',
            'car_model' => 'nullable|string|max:255',
            'car_color' => 'nullable|string|max:255',
            'photo_url' => 'nullable|string|max:255',

        ]);

        // Updating only if provided, otherwise keeping existing values
        $user->name = $validatedData['name'] ?? $user->name;
        $user->email = $validatedData['email'] ?? $user->email;
        $user->location = $validatedData['location'] ?? $user->location;
        $user->role = $validatedData['role'] ?? $user->role;
        $user->phone = $validatedData['phone'] ?? $user->phone;
        $user->photo_url = $validatedData['photo_url'] ?? $user->photo_url;
        $user->category = $validatedData['category'] ?? $user->category;
        $user->car_name = $validatedData['car_name'] ?? $user->car_name;
        $user->car_model = $validatedData['car_model'] ?? $user->car_model;
        $user->car_color = $validatedData['car_color'] ?? $user->car_color;

        if (! empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function userBookSngle(Request $request, $user_id)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Booking::where('user_id', $user_id);

        // Apply filters based on `created_at`
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $bookings = $query->orderBy('created_at', 'asc')->get(); // Get all fields

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'No bookings found for this user',
                'bookings' => [],
            ], 404);
        }

        return response()->json([
            'message' => 'User bookings retrieved successfully',
            'bookings' => $bookings,
        ]);
    }

    public function customerBookSngle(Request $request, $user_id)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Booking::where('customer_id', $user_id);

        // Apply filters based on `created_at`
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $bookings = $query->orderBy('created_at', 'asc')->get(); // Get all fields

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'No bookings found for this user',
                'bookings' => [],
            ], 404);
        }

        return response()->json([
            'message' => 'User bookings retrieved successfully',
            'bookings' => $bookings,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|integer',
        ]);

        // Find the user
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->status = $request->status;
        $user->save();

        // Check if the status is 2 (Ride Started) or 3 (Booking Completed)
        if (in_array($request->status, [2, 3])) {
            $this->sendAdminNotification($user, $request->status);
        }

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user,
        ], 200);
    }

    /**
     * Send a notification to all admins when the ride starts or completes.
     *
     * @param  int  $status
     */
    private function sendAdminNotification(User $user, $status)
    {
        // Fetch all admins
        $admins = User::where('role', 'admin')->whereNotNull('fcm_token')->get();

        if ($admins->isEmpty()) {
            \Log::warning('No admin found with FCM token.');

            return;
        }

        $firebaseService = new FirebaseNotificationService;

        // Define the message based on status
        $messageTitle = $status == 2 ? 'Ride Started' : 'Booking Completed';
        $messageBody = $status == 2
    ? "Driver {$user->name} has started the ride at ".now()->format('h:i A').'.'
    : "Driver {$user->name} has completed the booking at ".now()->format('h:i A').'.';

        // Send notification to all admins
        foreach ($admins as $admin) {
            $firebaseService->sendNotification(
                $admin->fcm_token,
                $messageTitle,
                $messageBody,
                ['user_id' => $user->id, 'status' => $status]
            );
        }
    }

    private function sendAdminNotificationStatus(User $user, $status)
    {
        // Fetch all admins
        $admins = User::admin()->whereNotNull('fcm_token')->get();

        if ($admins->isEmpty()) {
            \Log::warning('No admin found with FCM token.');

            return;
        }

        $firebaseService = new FirebaseNotificationService;

        // Define the message based on status
        $messageTitle = $status == 0 ? 'Driver Offline' : 'Driver Online';
        $messageBody = $status == 1
    ? "Driver {$user->name} is online at ".now()->format('h:i A').'.'
    : "Driver {$user->name} is offline at ".now()->format('h:i A').'.';

        // Send notification to all admins
        foreach ($admins as $admin) {
            $firebaseService->sendNotification(
                $admin->fcm_token,
                $messageTitle,
                $messageBody,
                ['user_id' => $user->id, 'status' => $status]
            );
        }
    }

    public function updateRating(Request $request, $id)
    {

        $request->validate([
            'rating_count' => 'required|integer|min:0',
            'rating' => ['required', 'numeric', 'between:0,5', 'regex:/^\d+(\.\d{1})?$/'],
        ]);
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->rating_count = $request->rating_count;
        $user->rating = $request->rating;
        $user->save();

        return response()->json([
            'message' => 'User rate updated successfully',
            'user' => $user,
        ], 200);
    }

    public function isActive(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'is_active' => 'required|integer|in:0,1',
        ]);

        // Find user
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update user's is_active
        $user->is_active = $request->is_active;
        $user->save();

        // Prepare data for online status log
        $onlineStatusData = [
            'driver_id' => $user->id,
            'is_active' => $request->is_active,
            'changed_at' => now(),

        ];

        if (! empty($user->car_name)) {
            $onlineStatusData['car_details'] = $user->car_name;
        }

        \App\Models\DriverOnlineStatus::create($onlineStatusData);

        if (in_array($request->is_active, [1, 0])) {
            $this->sendAdminNotificationStatus($user, $request->is_active);
        }

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user,
        ]);
    }

    public function getDriverOnlineStats(Request $request, $id)
    {
        $period = $request->query('period', 'week'); // 'day', 'week', or 'month'

        $query = \App\Models\DriverOnlineStatus::where('driver_id', $id)
            ->orderBy('changed_at');

        // Apply filter by period
        if ($period === 'day') {
            $query->whereBetween('changed_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ]);
        } elseif ($period === 'month') {
            $query->whereBetween('changed_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]);
        } else { // default to week
            $query->whereBetween('changed_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        }

        $logs = $query->get();

        $totalOnlineSeconds = 0;

        for ($i = 0; $i < $logs->count() - 1; $i++) {
            $current = $logs[$i];
            $next = $logs[$i + 1];

            if ($current->is_active == 1 && $next->is_active == 0) {
                $onlineTime = strtotime($next->changed_at) - strtotime($current->changed_at);
                if ($onlineTime > 0) {
                    $totalOnlineSeconds += $onlineTime;
                }
            }
        }

        // Convert to minutes only
        $totalMinutes = floor($totalOnlineSeconds / 60);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return response()->json([
            'driver_id' => $id,
            'period' => $period,
            'log_count' => $logs->count(),
            'total_minutes_online' => $totalMinutes,
            'formatted_time_online' => sprintf('%02d:%02d', $hours, $minutes),
            'logs' => $logs,
        ]);
    }
}
