<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // Register API
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'role' => 'required|string',
            'location' => 'required|string',
            'car_name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'car_model' => 'nullable|string|max:255',
            'car_color' => 'nullable|string|max:255',
            'photo_url' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'photo_url' => $validatedData['photo_url'] ?? null,
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'category' => $validatedData['category'],
            'location' => $validatedData['location'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
            'car_name' => $validatedData['car_name'] ?? null,
            'car_model' => $validatedData['car_model'] ?? null,
            'car_color' => $validatedData['car_color'] ?? null,
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    // Login API
    public function auth(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || $user->status == 1) {
            return response()->json(['message' => 'Account expired or inactive'], 403);
        }

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('API Token')->plainTextToken;

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'role' => $user->role,
            'category' => $user->category,
            'fcm_token' => $user->fcm_token,
            'token' => $token,
        ]);
    }

    // Logout API
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // Generate a unique reset token
        $token = Str::random(64);

        // Store the token in password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        // Send the reset token via email
        Mail::raw("Your password reset token is: $token", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Password Reset Token');
        });

        return response()->json(['message' => 'Reset token sent to email.'], 200);
    }

    public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'token' => 'required',
        'password' => 'required|min:6|confirmed'
    ]);

    // Find the token in the database
    $reset = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->where('token', $request->token)
        ->first();

    if (!$reset) {
        return response()->json(['error' => 'Invalid or expired token.'], 400);
    }

    // Update the user's password
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->save();

    // Delete the password reset record
    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json(['message' => 'Password reset successfully.'], 200);
}
}
