<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $user = User::withoutGlobalScope('tenant')
            ->where('invitation_token', $token)
            ->where('status', 'invited')
            ->first();

        if (! $user) {
            abort(404, 'This invitation link is invalid or has already been used.');
        }

        if ($user->invited_at && $user->invited_at->lt(Carbon::now()->subHours(72))) {
            abort(410, 'This invitation link has expired. Please contact your administrator.');
        }

        return view('auth.accept-invitation', compact('user', 'token'));
    }

    public function accept(Request $request, string $token)
    {
        $request->validate([
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $user = User::withoutGlobalScope('tenant')
            ->where('invitation_token', $token)
            ->where('status', 'invited')
            ->first();

        if (! $user) {
            return back()->withErrors(['token' => 'This invitation link is invalid or has already been used.']);
        }

        if ($user->invited_at && $user->invited_at->lt(Carbon::now()->subHours(72))) {
            return back()->withErrors(['token' => 'This invitation link has expired. Please contact your administrator.']);
        }

        $user->update([
            'password'         => Hash::make($request->password),
            'status'           => 'active',
            'invitation_token' => null,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return redirect('/manage')->with('success', 'Welcome to BMMS! Your account is now active.');
    }
}
