<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Exam\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Parent account sign-up (role guardian). Same fields and rules as the
 * student registration (RegisterRequest).
 */
class RegisterController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Guardian/Register');
    }

    public function store(RegisterRequest $request, ReferralService $referrals): RedirectResponse
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'guardian',
            'status' => 'active',
        ]);
        // same as the student sign-up: no SMS provider yet
        $user->forceFill(['phone_verified_at' => now()])->save();

        $referrals->captureForNewUser($user, $request);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('guardian.dashboard')
            ->with('success', 'Xush kelibsiz! Endi farzandingizni bog\'lang.');
    }
}
