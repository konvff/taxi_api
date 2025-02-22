<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'driver')->get();

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
            'email' => 'string|email|unique:users,email,'.$id,
            'phone' => 'string|max:255',
            'location' => 'string|max:255',
            'car_name' => 'nullable|string|max:255',
            'car_model' => 'nullable|string|max:255',
            'car_color' => 'nullable|string|max:255',

        ]);

        // Updating only if provided, otherwise keeping existing values
        $user->name = $validatedData['name'] ?? $user->name;
        $user->email = $validatedData['email'] ?? $user->email;
        $user->location = $validatedData['location'] ?? $user->location;
        $user->phone = $validatedData['phone'] ?? $user->phone;
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

    public function userBookings(Request $request)
    {
        $user = $request->user(); // Get authenticated user

        $bookings = Booking::where('user_id', $user->id)->get(); // Get user's bookings

        return response()->json([
            'message' => 'User bookings retrieved successfully',
            'bookings' => $bookings,
        ]);
    }
}
