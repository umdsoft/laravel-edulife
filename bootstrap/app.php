<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // Listeners are mapped explicitly in EventServiceProvider; auto-discovery
    // registered them a second time (double XP / duplicate certificates)
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            // exam stage 7: students without a complete exam profile -> onboarding
            // (alias() replaces the whole list, so every alias stays in this array)
            'exam.onboarded' => \App\Http\Middleware\EnsureExamOnboarded::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Rate-limited form posts (login 5/min, register 3/min) showed Laravel's raw
        // "429 TOO MANY REQUESTS" page in an Inertia modal; return a form error instead (QA-P-01)
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->isMethod('GET') || $request->expectsJson()) {
                return null;
            }

            $seconds = max(1, (int) ($e->getHeaders()['Retry-After'] ?? 60));
            $field = match (true) {
                $request->routeIs('login.submit') => 'login',
                $request->routeIs('register.submit') => 'phone',
                default => 'throttle',
            };

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([$field => "Juda ko'p urinish. {$seconds} soniyadan keyin qayta urinib ko'ring."]);
        });

        // Error pages in Uzbek with the app's look instead of Laravel's English
        // "403 THIS ACTION IS UNAUTHORIZED." page (QA2-S-12). 500/503 keep the
        // debug page while APP_DEBUG is on; JSON callers keep JSON.
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            $status = $response->getStatusCode();
            $friendly = config('app.debug') ? [403, 404, 419, 429] : [403, 404, 419, 429, 500, 503];

            if (! in_array($status, $friendly, true) || $request->expectsJson()) {
                return $response;
            }

            // an expired CSRF token on a form post: back to the form with a message
            if ($status === 419 && ! $request->isMethod('GET')) {
                return back()->with('error', "Sahifa eskirdi. Sahifani yangilab, qayta urinib ko'ring.");
            }

            return \Inertia\Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();