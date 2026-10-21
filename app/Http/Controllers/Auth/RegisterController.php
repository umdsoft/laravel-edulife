<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Exam\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Students and parents register only through the Telegram bot: the page
 * runs the phone + deep-link flow (docs/auth/TELEGRAM_LOGIN.md); there is
 * no password registration.
 */
class RegisterController extends Controller
{
    public function showRegister(Request $request, ReferralService $referrals): Response
    {
        // ?ref=CODE is kept until the account is created (like /r/{code})
        if (is_string($request->query('ref'))) {
            $referrals->remember($request, $request->query('ref'));
        }

        return Inertia::render('Auth/Register');
    }
}
