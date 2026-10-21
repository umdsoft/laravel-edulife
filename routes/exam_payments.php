<?php

/*
 * Paid exams (docs/exam/QARORLAR.md item 9, docs/payments/PAYMENTS.md): a student pays for one Saturday
 * exam (payment product "exam_session", through the shared /payments/checkout) or has a subscription
 * that includes exams. Here: the admin list of entries to refund by hand.
 */

use App\Http\Controllers\Admin\Exam\ExamRefundController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin/exam')->name('admin.exam.')->group(function () {
    Route::get('/refunds', [ExamRefundController::class, 'index'])->name('refunds.index');
    Route::post('/refunds/purchases/{purchase}', [ExamRefundController::class, 'markPurchase'])->name('refunds.purchase');
    // payment_orders are routed by uuid
    Route::post('/refunds/orders/{order}', [ExamRefundController::class, 'markDuplicate'])->name('refunds.order');
});
