<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class ApiBookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Booking::withTrashed()->get(), 200);
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

        if (!$booking) {
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

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'category' => 'required',
            'pickuplocation' => 'required',
            'destination' => 'required',
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

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->delete();
        return response()->json(['message' => 'Booking soft deleted'], 200);
    }

    public function restore($id)
    {
        $booking = Booking::onlyTrashed()->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->restore();
        return response()->json(['message' => 'Booking restored'], 200);
    }

    // Permanently delete a booking
    public function forceDelete($id)
    {
        $booking = Booking::withTrashed()->find($id);

        if (!$booking) {
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
                : "Driver assigned successfully",
            'booking' => $booking->load('user') // Load user details if relationship exists
        ]);
    }


}
