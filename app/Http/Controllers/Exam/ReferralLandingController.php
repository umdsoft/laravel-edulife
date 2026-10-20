<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /r/{code} — invite link from the result card QR and the progress page.
 * Remembers the code and opens the registration page (already signed-in
 * visitors just go home: a user is referred only at sign-up).
 */
class ReferralLandingController extends Controller
{
    public function __invoke(Request $request, string $code, ReferralService $referrals): RedirectResponse
    {
        if ($request->user()) {
            return redirect('/');
        }

        $referrals->remember($request, $code);

        return redirect()->route('register');
    }
}
