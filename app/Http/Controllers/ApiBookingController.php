<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiBookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = Booking::with('user')
            ->whereNull('deleted_at')
            ->get();

        return response()->json($bookings, 200);

    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer',
        ]);

        // Find the booking
        $booking = Booking::find($id);
        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->status = $request->status;
        $booking->save();
        $driver = auth()->user();

        if (in_array($request->status, [2, 3])) {
            $this->sendAdminNotification($driver, $request->status);
        }

        return response()->json([
            'message' => 'Booking status updated successfully',
            'booking' => $booking,
        ], 200);
    }

    /**
     * Send a notification to all admins when the ride starts or completes.
     *
     * @param  int  $status
     */
    private function sendAdminNotification(?User $driver, $status)
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
            ? "Driver {$driver->name} has started the ride."
            : "Driver {$driver->name} has completed the booking.";

        // Send notification to all admins
        foreach ($admins as $admin) {
            $firebaseService->sendNotification(
                $admin->fcm_token,
                $messageTitle,
                $messageBody,
                [
                    'booking_id' => $driver->id ?? null,
                    'status' => $status,
                ]
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|email',
            'category' => 'nullable',
            'pickuplocation' => 'required',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'destination' => 'required',
            'phone' => 'required',
            'dropoff_latitude' => 'nullable|numeric|between:-90,90',
            'dropoff_longitude' => 'nullable|numeric|between:-180,180',
            'amount' => 'nullable',
            'customer_id' => 'nullable|exists:users,id',
            'created_by' => 'nullable',
            'notes' => 'nullable|string',
            'booking_date' => 'nullable|date',
        ]);

        $booking = Booking::create($request->all());

        return response()->json($booking, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $booking = Booking::withTrashed()->find($id);

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        return response()->json($booking, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        try {
            $booking = Booking::find($id);

            if (! $booking) {
                return response()->json(['message' => 'Booking not found'], 404);
            }
            $request->validate([
                'name' => 'required',
                'email' => 'nullable|email',
                'category' => 'nullable',
                'pickuplocation' => 'required',
                'pickup_latitude' => 'nullable|numeric|between:-90,90',
                'pickup_longitude' => 'nullable|numeric|between:-180,180',
                'destination' => 'required',
                'phone' => 'required',
                'dropoff_latitude' => 'nullable|numeric|between:-90,90',
                'dropoff_longitude' => 'nullable|numeric|between:-180,180',
                'amount' => 'nullable',
                'notes' => 'nullable|string',
                'booking_date' => 'nullable|date',
            ]);

            $booking->update($request->all());

            return response()->json($booking, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $booking = Booking::find($id);

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking soft deleted'], 200);
    }

    public function restore($id)
    {
        $booking = Booking::onlyTrashed()->find($id);

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->restore();

        return response()->json(['message' => 'Booking restored'], 200);
    }

    // Permanently delete a booking
    public function forceDelete($id)
    {
        $booking = Booking::withTrashed()->find($id);

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->forceDelete();

        return response()->json(['message' => 'Booking permanently deleted'], 200);
    }

    public function assignDriver(Request $request, $bookingId)
    {
        $request->validate([
            'notes' => 'nullable',
            'user_id' => 'required|exists:users,id',
            'booking_date' => 'nullable|date_format:Y-m-d',
        ]);

        $booking = Booking::findOrFail($bookingId);
        $previousDriverId = $booking->user_id ?? null;

        $booking->user_id = $request->user_id;
        $booking->booking_date = $request->booking_date;
        $booking->notes = $request->notes;
        $booking->save();

        // Get the assigned driver's FCM token
        $driver = User::find($request->user_id);
        if ($driver && $driver->fcm_token) {
            $notificationService = new FirebaseNotificationService;
            $notificationService->sendNotification(
                $driver->fcm_token,
                'New Booking Assigned',
                'You have been assigned a new booking!',
                ['booking_id' => $bookingId]
            );
        }

        return response()->json([
            'message' => $previousDriverId
                ? "Driver reassigned successfully from Driver ID: $previousDriverId to Driver ID: {$request->user_id}"
                : 'Driver assigned successfully',
            'booking' => $booking->load('user'),
        ]);
    }

    public function assignCustomer(Request $request, $bookingId)
    {
        $request->validate([
            'notes' => 'nullable',
            'customer_id' => 'required|exists:users,id',
            'booking_date' => 'nullable|date_format:Y-m-d',
        ]);

        try {
            $booking = Booking::findOrFail($bookingId);
            $previousCustomerId = $booking->customer_id ?? null;

            $booking->customer_id = $request->customer_id;
            $booking->booking_date = $request->booking_date;
            $booking->notes = $request->notes;
            $booking->save();

            // Notify the assigned customer
            $customer = User::find($request->customer_id);
            if ($customer && $customer->fcm_token) {
                $notificationService = new FirebaseNotificationService;
                $notificationService->sendNotification(
                    $customer->fcm_token,
                    'New Booking Assigned',
                    'You have been assigned a new booking!',
                    ['booking_id' => $bookingId]
                );
            }

            return response()->json([
                'message' => $previousCustomerId
                    ? "Customer reassigned successfully from Customer ID: $previousCustomerId to Customer ID: {$request->customer_id}"
                    : 'Customer assigned successfully',
                'booking' => $booking->load('customer'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while assigning the customer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Function to send push notifications via Firebase Cloud Messaging (FCM)
     */
    public function getUserBookings(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $previousMonthStart = Carbon::now()->startOfMonth()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->startOfMonth()->subMonth()->endOfMonth();

        $queryOngoing = Booking::with('user')->where('status', 2);
        $queryUnassign = Booking::with('user')->where('status', 0);
        $queryCompleted = Booking::with('user')->where('status', 3);

        if ($startDate && $endDate) {
            $queryOngoing->whereBetween('updated_at', [$startDate, $endDate]);
            $queryCompleted->whereBetween('updated_at', [$startDate, $endDate]);
        }

        $onGoingBookings = $queryOngoing->get();
        $unassignBookings = $queryUnassign->get();
        $completedBookings = $queryCompleted->get();

        $onGoingRevenue = $onGoingBookings->sum('amount') ?? 0;
        $completedRevenue = $completedBookings->sum('amount') ?? 0;

        // Total revenue logic
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $totalRevenue = Booking::whereIn('status', [3])->sum('amount') ?? 0;
            } else {
                $totalRevenue = Booking::whereIn('status', [3])->sum('amount') ?? 0;
            }
        } else {
            $totalRevenue = Booking::whereIn('status', [3])->sum('amount') ?? 0;
        }

        // Daily revenue logic
        $dailyRevenue = 0;
        if ($startDate && $endDate && $startDate === $endDate) {
            $dailyRevenue = Booking::where('status', 3)
                ->whereDate('updated_at', $startDate)
                ->sum('amount') ?? 0;
        } else {
            $dailyRevenue = Booking::where('status', 3)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->sum('amount') ?? 0;
        }

        $previousMonthRevenue = Booking::whereBetween('updated_at', [$previousMonthStart, $previousMonthEnd])
            ->where('status', 3)
            ->sum('amount') ?? 0;

        $currentMonthRevenue = Booking::whereBetween('updated_at', [$currentMonthStart, $currentMonthEnd])
            ->where('status', 3)
            ->sum('amount') ?? 0;

        return response()->json([
            'filter_applied' => $startDate && $endDate ? true : false,
            'start_date' => $startDate ?? 'All Data',
            'end_date' => $endDate ?? 'All Data',
            'onGoing' => [
                'bookings' => $onGoingBookings,
                'revenue' => $onGoingRevenue,
            ],
            'UnAssign' => [
                'bookings' => $unassignBookings,
            ],
            'completed' => [
                'bookings' => $completedBookings,
                'revenue' => $completedRevenue,
            ],
            'total_revenue' => $totalRevenue,
            'filter_revenue' => $dailyRevenue,
            'previous_month_revenue' => $previousMonthRevenue,
            'current_month_revenue' => $currentMonthRevenue,
        ], 200);
    }

    public function getDriverBookings(Request $request, $userId): JsonResponse
    {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $previousMonthStart = Carbon::now()->startOfMonth()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->startOfMonth()->subMonth()->endOfMonth();

        $queryOngoing = Booking::with('user')->where('user_id', $userId)->where('status', 2);
        $queryUnassign = Booking::with('user')->where('user_id', $userId)->where('status', 0);
        $queryCompleted = Booking::with('user')->where('user_id', $userId)->where('status', 3);

        if ($startDate && $endDate) {
            $queryOngoing->whereBetween('updated_at', [$startDate, $endDate]);
            $queryCompleted->whereBetween('updated_at', [$startDate, $endDate]);
        }

        $onGoingBookings = $queryOngoing->get();
        $unassignBookings = $queryUnassign->get();
        $completedBookings = $queryCompleted->get();

        $onGoingRevenue = $onGoingBookings->sum('amount') ?? 0;
        $completedRevenue = $completedBookings->sum('amount') ?? 0;

        // Total revenue logic for driver
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $totalRevenue = Booking::where('user_id', $userId)
                    ->whereIn('status', [3])
                    ->sum('amount') ?? 0;
            } else {
                $totalRevenue = Booking::where('user_id', $userId)
                    ->whereIn('status', [3])
                    ->sum('amount') ?? 0;
            }
        } else {
            $totalRevenue = Booking::where('user_id', $userId)
                ->whereIn('status', [3])
                ->sum('amount') ?? 0;
        }

        // Daily revenue logic for driver
        $dailyRevenue = 0;
        if ($startDate && $endDate && $startDate === $endDate) {
            $dailyRevenue = Booking::where('user_id', $userId)
                ->where('status', 3)
                ->whereDate('updated_at', $startDate)
                ->sum('amount') ?? 0;
        } else {
            $dailyRevenue = Booking::where('user_id', $userId)
                ->where('status', 3)
                ->whereBetween('updated_at', [$startDate, $endDate])

                ->sum('amount') ?? 0;
        }

        $previousMonthRevenue = Booking::where('user_id', $userId)
            ->whereBetween('updated_at', [$previousMonthStart, $previousMonthEnd])
            ->where('status', 3)
            ->sum('amount') ?? 0;

        $currentMonthRevenue = Booking::where('user_id', $userId)
            ->whereBetween('updated_at', [$currentMonthStart, $currentMonthEnd])
            ->where('status', 3)
            ->sum('amount') ?? 0;

        return response()->json([
            'user_id' => $userId,
            'filter_applied' => $startDate && $endDate ? true : false,
            'start_date' => $startDate ?? 'All Data',
            'end_date' => $endDate ?? 'All Data',
            'onGoing' => [
                'bookings' => $onGoingBookings,
                'revenue' => $onGoingRevenue,
            ],
            'UnAssign' => [
                'bookings' => $unassignBookings,
            ],
            'completed' => [
                'bookings' => $completedBookings,
                'revenue' => $completedRevenue,
            ],
            'total_revenue' => $totalRevenue,
            'filter_revenue' => $dailyRevenue,
            'previous_month_revenue' => $previousMonthRevenue,
            'current_month_revenue' => $currentMonthRevenue,
        ], 200);
    }

    public function getBookingsByDate(Request $request)
    {
        $request->validate([
            'booking_date' => 'required|date_format:Y-m-d',
        ]);

        $bookingDate = $request->query('booking_date');

        // Debugging output
        \Log::info("Fetching bookings for date: $bookingDate");

        $bookings = Booking::whereDate('booking_date', $bookingDate)->get();

        if ($bookings->isEmpty()) {
            \Log::info("No bookings found for date: $bookingDate");

            return response()->json(['message' => 'No bookings found for this date.'], 404);
        }

        return response()->json($bookings);
    }

    public function updateBookingDate(Request $request, $bookingId)
    {
        $request->validate([
            'booking_date' => 'required|date_format:Y-m-d',
        ]);

        $booking = Booking::findOrFail($bookingId);
        $previousBookingDate = $booking->booking_date;

        $booking->booking_date = $request->booking_date;
        $booking->save();

        return response()->json([
            'message' => "Booking date updated successfully from $previousBookingDate to {$request->booking_date}",
            'booking' => $booking,
        ]);
    }
}
