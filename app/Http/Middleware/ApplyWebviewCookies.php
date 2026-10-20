<?php

namespace App\Http\Middleware;

use App\Services\Telegram\WebviewSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telegram WebView (wave 2): Telegram Web (web.telegram.org) runs the Mini App in a
 * cross-site iframe, where SameSite=Lax cookies are neither stored nor sent. For
 * Mini App sessions on HTTPS the session and XSRF-TOKEN cookies are therefore issued
 * SameSite=None; Secure (and Partitioned — CHIPS — so browsers that block third-party
 * cookies keep them per top-level site). Plain HTTP (local) and website sessions keep
 * config/session.php untouched.
 *
 * Runs inside StartSession/VerifyCsrfToken (web group, appended): both read
 * config('session') when they add their cookies to the response, after this returns.
 */
class ApplyWebviewCookies
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isSecure() && ($this->isMiniAppEntry($request) || WebviewSession::active($request))) {
            $sameSite = strtolower((string) config('exam.telegram.webview_cookie.same_site', 'none'));
            if (in_array($sameSite, ['none', 'lax', 'strict'], true)) {
                config([
                    'session.same_site' => $sameSite,
                    'session.secure' => true,
                    'session.partitioned' => $sameSite === 'none' && (bool) config('exam.telegram.webview_cookie.partitioned', true),
                ]);
            }
        }

        return $response;
    }

    private function isMiniAppEntry(Request $request): bool
    {
        return $request->is('telegram/app', 'telegram/app/*');
    }
}
