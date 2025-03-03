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
            'email' => 'required|email|unique:users,email',
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
            'email' => 'required|email|unique:users,email',
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
        // Check if filters are provided
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Query Builder
        $queryOngoing = Booking::with('user')->where('status', 2);
        $queryCompleted = Booking::with('user')->where('status', 3);

        // Apply date filter only if both start and end dates are provided
        if ($startDate && $endDate) {
            $queryOngoing->whereBetween('created_at', [$startDate, $endDate]);
            $queryCompleted->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Fetch Data
        $onGoingBookings = $queryOngoing->get();
        $completedBookings = $queryCompleted->get();

        // Calculate Revenue
        $onGoingRevenue = $onGoingBookings->sum('amount');
        $completedRevenue = $completedBookings->sum('amount');
        $totalRevenue = $onGoingRevenue + $completedRevenue;

        return response()->json([
            'filter_applied' => $startDate && $endDate ? true : false,
            'start_date' => $startDate ?? 'All Data',
            'end_date' => $endDate ?? 'All Data',
            'onGoing' => [
                'bookings' => $onGoingBookings,
                'revenue' => $onGoingRevenue,
            ],
            'completed' => [
                'bookings' => $completedBookings,
                'revenue' => $completedRevenue,
            ],
            'total_revenue' => $totalRevenue,
        ], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        // Validate the request
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
