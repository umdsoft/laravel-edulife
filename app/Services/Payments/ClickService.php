<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Click SHOP API: Click calls Prepare (action=0) and Complete (action=1) with
 * application/x-www-form-urlencoded bodies and expects JSON answers.
 *
 * Requests — https://docs.click.uz/shop-api/requests
 *   Prepare  sign_string = md5(click_trans_id + service_id + SECRET_KEY + merchant_trans_id + amount + action + sign_time)
 *   Complete sign_string = md5(click_trans_id + service_id + SECRET_KEY + merchant_trans_id + merchant_prepare_id + amount + action + sign_time)
 * Errors   — https://docs.click.uz/shop-api/errors
 *   0 Success · -1 SIGN CHECK FAILED · -2 Incorrect parameter amount · -3 Action not found · -4 Already paid
 *   -5 User does not exist · -6 Transaction does not exist · -7 Failed to update user
 *   -8 Error in request from click · -9 Transaction cancelled
 *
 * merchant_trans_id is our order number (PaymentOrder id); merchant_prepare_id /
 * merchant_confirm_id are our PaymentTransaction id.
 */
class ClickService
{
    public const SUCCESS = 0;
    public const SIGN_FAILED = -1;
    public const INVALID_AMOUNT = -2;
    public const ACTION_NOT_FOUND = -3;
    public const ALREADY_PAID = -4;
    public const ORDER_NOT_FOUND = -5;
    public const TRANSACTION_NOT_FOUND = -6;
    public const UPDATE_FAILED = -7;
    public const BAD_REQUEST = -8;
    public const CANCELLED = -9;

    private const NOTES = [
        self::SUCCESS => 'Success',
        self::SIGN_FAILED => 'SIGN CHECK FAILED!',
        self::INVALID_AMOUNT => 'Incorrect parameter amount',
        self::ACTION_NOT_FOUND => 'Action not found',
        self::ALREADY_PAID => 'Already paid',
        self::ORDER_NOT_FOUND => 'User does not exist',
        self::TRANSACTION_NOT_FOUND => 'Transaction does not exist',
        self::UPDATE_FAILED => 'Failed to update user',
        self::BAD_REQUEST => 'Error in request from click',
        self::CANCELLED => 'Transaction cancelled',
    ];

    private const PREPARE_FIELDS = ['click_trans_id', 'service_id', 'click_paydoc_id', 'merchant_trans_id', 'amount', 'action', 'error', 'sign_time', 'sign_string'];

    public function __construct(
        private readonly PaymentProducts $products,
        private readonly PaymentFulfillmentService $fulfilment,
    ) {}

    public static function configured(): bool
    {
        return filled(config('payments.click.service_id'))
            && filled(config('payments.click.merchant_id'))
            && filled(config('payments.click.secret_key'));
    }

    /** action = 0 */
    public function prepare(array $params, ?string $ip = null): array
    {
        if ($error = $this->validate($params, 0, $ip)) {
            return $error;
        }

        $clickId = (string) $params['click_trans_id'];
        $order = $this->order($params['merchant_trans_id']);
        if (! $order) {
            return $this->error($params, self::ORDER_NOT_FOUND);
        }
        if ($this->tiyin($params['amount']) !== (int) $order->amount_tiyin) {
            return $this->error($params, self::INVALID_AMOUNT);
        }

        $existing = $this->transactionByClickId($clickId);
        if ($existing) {
            return $this->repeatPrepare($params, $existing, $order);
        }

        if ($order->isPaid()) {
            return $this->error($params, self::ALREADY_PAID);
        }
        if (! $order->isPending() || $this->products->problem($order) !== null) {
            return $this->error($params, self::CANCELLED);
        }
        // Click reports an error at the prepare stage: nothing to reserve
        if ((int) $params['error'] < 0) {
            return $this->error($params, self::CANCELLED);
        }

        try {
            $transaction = DB::transaction(function () use ($order, $clickId, $params) {
                PaymentOrder::whereKey($order->id)->lockForUpdate()->first();

                $transaction = PaymentTransaction::create([
                    'payment_order_id' => $order->id,
                    'provider' => PaymentOrder::PROVIDER_CLICK,
                    'provider_transaction_id' => $clickId,
                    'state' => PaymentTransaction::STATE_CREATED,
                    'amount_tiyin' => $this->tiyin($params['amount']),
                    'create_time' => PaymentTransaction::nowMs(),
                    'click_paydoc_id' => (string) $params['click_paydoc_id'],
                    'meta' => ['sign_time' => (string) $params['sign_time']],
                ]);
                $this->syncPayment($order, 'processing', $clickId);

                return $transaction;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->repeatPrepare($params, $this->transactionByClickId($clickId), $order);
        }

        Log::info('click.prepare', ['order' => $order->id, 'transaction' => $transaction->id]);

        return $this->success($params, ['merchant_prepare_id' => $transaction->id]);
    }

    /** action = 1 */
    public function complete(array $params, ?string $ip = null): array
    {
        if ($error = $this->validate($params, 1, $ip)) {
            return $error;
        }

        $order = $this->order($params['merchant_trans_id']);
        if (! $order) {
            return $this->error($params, self::ORDER_NOT_FOUND);
        }

        $prepareId = $params['merchant_prepare_id'];
        $transaction = ctype_digit((string) $prepareId)
            ? PaymentTransaction::where('id', (int) $prepareId)
                ->where('provider', PaymentOrder::PROVIDER_CLICK)
                ->where('payment_order_id', $order->id)
                ->where('provider_transaction_id', (string) $params['click_trans_id'])
                ->first()
            : null;
        if (! $transaction) {
            return $this->error($params, self::TRANSACTION_NOT_FOUND);
        }
        if ($this->tiyin($params['amount']) !== (int) $order->amount_tiyin) {
            return $this->error($params, self::INVALID_AMOUNT);
        }

        try {
            $code = DB::transaction(function () use ($transaction, $params) {
                $transaction = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->first();
                $order = PaymentOrder::whereKey($transaction->payment_order_id)->lockForUpdate()->first();

                if ($transaction->state === PaymentTransaction::STATE_PERFORMED) {
                    return self::ALREADY_PAID;
                }
                if ($transaction->state !== PaymentTransaction::STATE_CREATED) {
                    return self::CANCELLED;
                }

                // Click could not take the money: cancel and answer -9 (Errors page)
                if ((int) $params['error'] < 0) {
                    $this->cancel($transaction, $order, (string) ($params['error_note'] ?? ''));

                    return self::CANCELLED;
                }

                if ($order->isPaid()) {
                    // paid by another Click transaction already
                    $this->cancel($transaction, $order, 'order already paid');

                    return self::ALREADY_PAID;
                }
                if (! $order->isPending()) {
                    $this->cancel($transaction, $order, 'order not pending');

                    return self::CANCELLED;
                }

                $transaction->forceFill([
                    'state' => PaymentTransaction::STATE_PERFORMED,
                    'perform_time' => PaymentTransaction::nowMs(),
                ])->save();

                $this->fulfilment->fulfil($order, $transaction);

                return self::SUCCESS;
            });
        } catch (\Throwable $e) {
            report($e);

            return $this->error($params, self::UPDATE_FAILED);
        }

        Log::info('click.complete', ['order' => $order->id, 'transaction' => $transaction->id, 'code' => $code]);

        return $code === self::SUCCESS
            ? $this->success($params, ['merchant_confirm_id' => $transaction->id])
            : $this->error($params, $code);
    }

    // ---------------------------------------------------------------- helpers

    /** Required fields, action, signature and service id. Returns an error response or null. */
    private function validate(array $params, int $action, ?string $ip): ?array
    {
        if (config('payments.click.ip_check') && ! in_array($ip, config('payments.click.allowed_ips', []), true)) {
            return $this->error($params, self::BAD_REQUEST);
        }

        $fields = $action === 1 ? [...self::PREPARE_FIELDS, 'merchant_prepare_id'] : self::PREPARE_FIELDS;
        foreach ($fields as $field) {
            if (! isset($params[$field]) || ! is_scalar($params[$field]) || (string) $params[$field] === '') {
                return $this->error($params, self::BAD_REQUEST);
            }
        }
        if (! is_numeric($params['amount']) || ! is_numeric($params['error'])) {
            return $this->error($params, self::BAD_REQUEST);
        }

        if ((string) $params['action'] !== (string) $action) {
            return $this->error($params, self::ACTION_NOT_FOUND);
        }

        if (! hash_equals($this->sign($params, $action), strtolower((string) $params['sign_string']))) {
            return $this->error($params, self::SIGN_FAILED);
        }

        if ((string) $params['service_id'] !== (string) config('payments.click.service_id')) {
            return $this->error($params, self::BAD_REQUEST);
        }

        return null;
    }

    /** The values exactly as Click sent them (amount keeps its own format). */
    public function sign(array $params, int $action): string
    {
        $parts = [
            $params['click_trans_id'] ?? '',
            $params['service_id'] ?? '',
            (string) config('payments.click.secret_key'),
            $params['merchant_trans_id'] ?? '',
        ];
        if ($action === 1) {
            $parts[] = $params['merchant_prepare_id'] ?? '';
        }
        array_push($parts, $params['amount'] ?? '', $params['action'] ?? '', $params['sign_time'] ?? '');

        return md5(implode('', array_map('strval', $parts)));
    }

    private function order(mixed $merchantTransId): ?PaymentOrder
    {
        if (! is_scalar($merchantTransId) || ! ctype_digit((string) $merchantTransId)) {
            return null;
        }

        return PaymentOrder::where('id', (int) $merchantTransId)
            ->where('provider', PaymentOrder::PROVIDER_CLICK)
            ->first();
    }

    private function transactionByClickId(string $clickId): ?PaymentTransaction
    {
        return PaymentTransaction::where('provider', PaymentOrder::PROVIDER_CLICK)
            ->where('provider_transaction_id', $clickId)
            ->first();
    }

    /** Prepare repeated for the same click_trans_id: the same answer. */
    private function repeatPrepare(array $params, PaymentTransaction $transaction, PaymentOrder $order): array
    {
        if ($transaction->payment_order_id !== $order->id) {
            return $this->error($params, self::TRANSACTION_NOT_FOUND);
        }

        return match ($transaction->state) {
            PaymentTransaction::STATE_CREATED => $this->success($params, ['merchant_prepare_id' => $transaction->id]),
            PaymentTransaction::STATE_PERFORMED => $this->error($params, self::ALREADY_PAID),
            default => $this->error($params, self::CANCELLED),
        };
    }

    private function cancel(PaymentTransaction $transaction, PaymentOrder $order, string $note): void
    {
        $meta = $transaction->meta ?? [];
        $meta['cancel_note'] = mb_substr($note, 0, 255);
        $transaction->forceFill([
            'state' => PaymentTransaction::STATE_CANCELLED,
            'cancel_time' => PaymentTransaction::nowMs(),
            'meta' => $meta,
        ])->save();
        $this->syncPayment($order, 'cancelled');
    }

    private function syncPayment(PaymentOrder $order, string $status, ?string $providerId = null): void
    {
        if (! $order->payment_id || $order->isPaid()) {
            return;
        }

        Payment::whereKey($order->payment_id)->whereIn('status', ['pending', 'processing', 'cancelled'])->update(array_filter([
            'status' => $status,
            'provider_transaction_id' => $providerId,
        ]));
    }

    private function tiyin(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function success(array $params, array $extra): array
    {
        return [
            'click_trans_id' => $this->intOrNull($params['click_trans_id'] ?? null),
            'merchant_trans_id' => (string) ($params['merchant_trans_id'] ?? ''),
            ...$extra,
            'error' => self::SUCCESS,
            'error_note' => self::NOTES[self::SUCCESS],
        ];
    }

    private function error(array $params, int $code): array
    {
        Log::info('click.error', ['code' => $code, 'merchant_trans_id' => is_scalar($params['merchant_trans_id'] ?? null) ? $params['merchant_trans_id'] : null]);

        return [
            'click_trans_id' => $this->intOrNull($params['click_trans_id'] ?? null),
            'merchant_trans_id' => is_scalar($params['merchant_trans_id'] ?? null) ? (string) $params['merchant_trans_id'] : null,
            'error' => $code,
            'error_note' => self::NOTES[$code],
        ];
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_scalar($value) && ctype_digit((string) $value) ? (int) $value : null;
    }
}
