<?php

namespace App\Http\Middleware;

use App\Models\Exam\StudentExamProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a student without a complete exam profile (subjects + parent's
 * consent, StudentExamProfile::isComplete()) to student.exam.onboarding.
 * Alias: exam.onboarded (bootstrap/app.php).
 */
class EnsureExamOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->role === 'student') {
            $profile = StudentExamProfile::query()->where('user_id', $user->id)->first();
            if (! $profile || ! $profile->isComplete()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Avval imtihon profilini to\'ldiring.', 'redirect' => route('student.exam.onboarding')], 409);
                }

                return redirect()->route('student.exam.onboarding')
                    ->with('info', 'Imtihonlarda qatnashish uchun avval qisqa anketani to\'ldiring.');
            }
        }

        return $next($request);
    }
}
