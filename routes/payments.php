<?php

use App\Http\Controllers\Payments\CheckoutController;
use App\Http\Controllers\Payments\ClickController;
use App\Http\Controllers\Payments\PaymeController;
use App\Http\Controllers\Payments\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Online payments (Payme, Click) — see docs/payments/PAYMENTS.md
|--------------------------------------------------------------------------
|
| Provider callbacks run WITHOUT the "web" group: no session, cookies or CSRF
| token (Payme / Click cannot send one) and no auth — they are authenticated by
| Payme's Basic auth key and Click's MD5 sign_string instead (optional IP
| allowlists in config/payments.php).
|
|   Payme "Endpoint URL":  POST {APP_URL}/payments/payme
|   Click "Prepare URL":   POST {APP_URL}/payments/click/prepare
|   Click "Complete URL":  POST {APP_URL}/payments/click/complete
*/

Route::withoutMiddleware(['web'])->prefix('payments')->name('payments.')->group(function () {
    // any method: a non-POST request gets the JSON-RPC error -32300
    Route::any('/payme', PaymeController::class)->name('payme');
    Route::post('/click/prepare', [ClickController::class, 'prepare'])->name('click.prepare');
    Route::post('/click/complete', [ClickController::class, 'complete'])->name('click.complete');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::post('/quote', [CheckoutController::class, 'quote'])->middleware('throttle:30,1')->name('quote');
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout');
        // the provider sends the student back here
        Route::get('/orders/{order}', [CheckoutController::class, 'show'])->name('return');
        Route::get('/orders/{order}/pay', [CheckoutController::class, 'pay'])->name('pay');
    });

    Route::get('/student/subscriptions', [SubscriptionPlanController::class, 'index'])->name('student.subscriptions.index');
});
