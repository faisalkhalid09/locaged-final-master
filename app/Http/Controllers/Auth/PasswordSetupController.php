<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordSetupController extends Controller
{
    /**
     * Show the password setup form
     */
    public function show(Request $request, User $user)
    {
        // Validate the signed URL
        if (!$request->hasValidSignature()) {
            abort(403, 'This password setup link has expired or is invalid.');
        }

        // Check if user already has a password set (basic check)
        // Note: All users have a password, but invited users have a random one
        // We'll allow setup even if password exists, for simplicity
        
        return view('auth.setup-password', compact('user'));
    }

    /**
     * Store the new password
     */
    public function store(Request $request, User $user)
    {
        // Validate the signed URL
        if (!$request->hasValidSignature()) {
            abort(403, 'This password setup link has expired or is invalid.');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        // Update the user's password
        $user->update([
            'password' => Hash::make($data['password']),
            'active' => true, // Ensure user is active
        ]);

        // Log the user in automatically
        auth()->login($user);

        return redirect()->route('home')->with('success', 'Your password has been set successfully. Welcome!');
    }
}
