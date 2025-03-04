<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'category' => 'required',
            'pickuplocation' => 'required',
            'destination' => 'required',
            'amount' => 'required',
            'notes' => 'nullable',
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
        $booking = Booking::find($id);

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'category' => 'required',
            'pickuplocation' => 'required',
            'destination' => 'required',
            'amount' => 'required',
            'notes' => 'nullable',
        ]);

        $booking->update($request->all());

        return response()->json($booking, 200);
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
            'user_id' => 'required|exists:users,id',
        ]);

        $booking = Booking::findOrFail($bookingId);
        if ($booking->user_id) {
            $previousDriverId = $booking->user_id;
        } else {
            $previousDriverId = null;
        }

        $booking->user_id = $request->user_id;
        $booking->save();

        return response()->json([
            'message' => $previousDriverId
                ? "Driver reassigned successfully from Driver ID: $previousDriverId to Driver ID: {$request->user_id}"
                : 'Driver assigned successfully',
            'booking' => $booking->load('user'), // Load user details if relationship exists
        ]);
    }

    public function getUserBookings(Request $request): JsonResponse
    {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $previousMonthStart = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        $queryOngoing = Booking::with('user')->where('status', 2);
        $queryUnassign = Booking::with('user')->where('status', 0);
        $queryCompleted = Booking::with('user')->where('status', 3);

        if ($startDate && $endDate) {
            $queryOngoing->whereBetween('created_at', [$startDate, $endDate]);
            $queryCompleted->whereBetween('created_at', [$startDate, $endDate]);
        }

        $onGoingBookings = $queryOngoing->get();
        $unassignBookings = $queryUnassign->get();
        $completedBookings = $queryCompleted->get();

        $onGoingRevenue = $onGoingBookings->sum('amount');
        $completedRevenue = $completedBookings->sum('amount');
        $totalRevenue = $onGoingRevenue + $completedRevenue;

        $previousMonthRevenue = Booking::whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->where('status', 3)
            ->sum('amount');

        $currentMonthRevenue = Booking::whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->where('status', 3)
            ->sum('amount');

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
            'previous_month_revenue' => $previousMonthRevenue,
            'current_month_revenue' => $currentMonthRevenue,
        ], 200);
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

        // Update the status
        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Booking status updated successfully',
            'booking' => $booking,
        ], 200);
    }
}
