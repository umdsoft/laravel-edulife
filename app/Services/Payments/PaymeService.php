<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payme Business Merchant API (JSON-RPC 2.0 over HTTPS POST; Payme calls us).
 *
 * Methods  — https://developer.help.paycom.uz/metody-merchant-api/
 *   CheckPerformTransaction — .../checkperformtransaction
 *   CreateTransaction       — .../createtransaction   (12 h timeout → state -1, reason 4)
 *   PerformTransaction      — .../performtransaction
 *   CancelTransaction       — .../canceltransaction   (state 1 → -1, state 2 → -2)
 *   CheckTransaction        — .../checktransaction
 *   GetStatement            — .../getstatement        (from <= time <= to, ascending by time)
 *   SetFiscalData           — .../setfiscaldata       (optional)
 * Auth     — "Authorization: Basic base64(Paycom:<key>)"; the TEST_KEY in the sandbox
 *            (https://developer.help.paycom.uz/protokol-merchant-api/skhema-vzaimodeystviya)
 * Errors   — https://developer.help.paycom.uz/metody-merchant-api/oshibki-errors
 * Types    — https://developer.help.paycom.uz/metody-merchant-api/tipy-dannykh (amounts in tiyin, ms timestamps)
 *
 * Repeated CreateTransaction / PerformTransaction / CancelTransaction calls return the same
 * answer as the first one (the sandbox sends each of them twice).
 */
class PaymeService
{
    private const METHODS = [
        'CheckPerformTransaction' => 'checkPerformTransaction',
        'CreateTransaction' => 'createTransaction',
        'PerformTransaction' => 'performTransaction',
        'CancelTransaction' => 'cancelTransaction',
        'CheckTransaction' => 'checkTransaction',
        'GetStatement' => 'getStatement',
        'SetFiscalData' => 'setFiscalData',
    ];

    public function __construct(
        private readonly PaymentProducts $products,
        private readonly PaymentFulfillmentService $fulfilment,
    ) {}

    public static function configured(): bool
    {
        return filled(config('payments.payme.merchant_id')) && filled(self::activeKey());
    }

    /** The password Payme sends: TEST_KEY in test mode, the production key otherwise. */
    public static function activeKey(): ?string
    {
        return config('payments.payme.test_mode')
            ? config('payments.payme.test_key')
            : config('payments.payme.key');
    }

    /**
     * Handle one JSON-RPC request; always returns the response body (HTTP 200).
     */
    public function handle(Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);
        $id = is_array($payload) ? ($payload['id'] ?? null) : null;
        $method = is_array($payload) && is_string($payload['method'] ?? null) ? $payload['method'] : null;

        try {
            if (! $request->isMethod('POST')) {
                throw PaymeException::make(PaymeException::NOT_POST);
            }
            $this->authorize($request);

            if (! is_array($payload)) {
                throw PaymeException::make(PaymeException::PARSE_ERROR);
            }
            if ($method === null || ! is_array($payload['params'] ?? null)) {
                throw PaymeException::make(PaymeException::INVALID_REQUEST);
            }
            if (! isset(self::METHODS[$method])) {
                throw PaymeException::make(PaymeException::METHOD_NOT_FOUND, $method);
            }

            $result = $this->{self::METHODS[$method]}($payload['params']);
            Log::info('payme.request', ['method' => $method, 'ok' => true]);

            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        } catch (PaymeException $e) {
            Log::info('payme.request', ['method' => $method, 'error' => $e->getCode()]);

            return ['jsonrpc' => '2.0', 'id' => $id, 'error' => $e->toArray()];
        } catch (\Throwable $e) {
            report($e);

            return ['jsonrpc' => '2.0', 'id' => $id, 'error' => PaymeException::make(PaymeException::SYSTEM_ERROR)->toArray()];
        }
    }

    // ---------------------------------------------------------------- methods

    /** amount, account → {allow: true[, detail]} | -31001 | -31050…-31099 */
    private function checkPerformTransaction(array $params): array
    {
        $amount = $this->amountParam($params);
        $order = $this->orderFromAccount($params);
        $this->assertPayable($order);
        $this->assertAmount($order, $amount);

        $result = ['allow' => true];
        if ($detail = $this->fiscalDetail($order)) {
            $result['detail'] = $detail;
        }

        return $result;
    }

    /** id, time, amount, account → {create_time, transaction, state} | -31001 | -31008 | -31050… */
    private function createTransaction(array $params): array
    {
        $paymeId = $this->idParam($params);
        $time = $params['time'] ?? null;
        if (! is_int($time) && ! (is_numeric($time) && (string) (int) $time === (string) $time)) {
            throw PaymeException::make(PaymeException::INVALID_REQUEST);
        }
        $amount = $this->amountParam($params);

        $existing = $this->transaction($paymeId);
        if ($existing) {
            return $this->repeatCreate($existing);
        }

        $order = $this->orderFromAccount($params);

        try {
            return DB::transaction(function () use ($order, $paymeId, $time, $amount, $params) {
                /** @var PaymentOrder $order */
                $order = PaymentOrder::whereKey($order->id)->lockForUpdate()->first();

                // a retry that raced with the first request
                if ($existing = $this->transaction($paymeId)) {
                    if ($existing->state !== PaymentTransaction::STATE_CREATED || $this->timedOut($existing)) {
                        throw PaymeException::make(PaymeException::CANNOT_PERFORM);
                    }

                    return $this->createResult($existing);
                }

                $this->assertPayable($order);
                $this->assertAmount($order, $amount);

                // one-time account: only one live transaction per order
                $active = $order->transactions()
                    ->where('provider', PaymentOrder::PROVIDER_PAYME)
                    ->whereIn('state', [PaymentTransaction::STATE_CREATED, PaymentTransaction::STATE_PERFORMED])
                    ->lockForUpdate()
                    ->get();
                foreach ($active as $other) {
                    if ($other->state === PaymentTransaction::STATE_CREATED && $this->timedOut($other)) {
                        $this->cancelByTimeout($other);

                        continue;
                    }
                    throw PaymeException::make(PaymeException::CANNOT_PERFORM);
                }

                $transaction = PaymentTransaction::create([
                    'payment_order_id' => $order->id,
                    'provider' => PaymentOrder::PROVIDER_PAYME,
                    'provider_transaction_id' => $paymeId,
                    'state' => PaymentTransaction::STATE_CREATED,
                    'amount_tiyin' => $amount,
                    'provider_time' => (int) $time,
                    'create_time' => PaymentTransaction::nowMs(),
                    'account' => $params['account'],
                ]);

                $this->syncPayment($order, 'processing', $paymeId);

                return $this->createResult($transaction);
            });
        } catch (UniqueConstraintViolationException) {
            // the same Payme id was inserted by a parallel retry
            return $this->repeatCreate($this->transaction($paymeId));
        }
    }

    /** id → {transaction, perform_time, state} | -31003 | -31008 */
    private function performTransaction(array $params): array
    {
        $paymeId = $this->idParam($params);
        if (! $this->transaction($paymeId)) {
            throw PaymeException::make(PaymeException::TRANSACTION_NOT_FOUND);
        }

        $result = DB::transaction(function () use ($paymeId) {
            $transaction = PaymentTransaction::where('provider', PaymentOrder::PROVIDER_PAYME)
                ->where('provider_transaction_id', $paymeId)
                ->lockForUpdate()
                ->first();

            if ($transaction->state === PaymentTransaction::STATE_PERFORMED) {
                return $this->performResult($transaction);
            }
            if ($transaction->state !== PaymentTransaction::STATE_CREATED) {
                throw PaymeException::make(PaymeException::CANNOT_PERFORM);
            }
            if ($this->timedOut($transaction)) {
                // the cancellation must be committed, so the error is thrown after the DB transaction
                $this->cancelByTimeout($transaction);

                return null;
            }

            $order = $transaction->order;
            if ($order->isPaid() && $order->fulfilled_at) {
                // the order was paid by another transaction (should not happen: one live transaction per order)
                throw PaymeException::make(PaymeException::CANNOT_PERFORM);
            }

            $transaction->forceFill([
                'state' => PaymentTransaction::STATE_PERFORMED,
                'perform_time' => PaymentTransaction::nowMs(),
            ])->save();

            $this->fulfilment->fulfil($order, $transaction);

            return $this->performResult($transaction);
        });

        if ($result === null) {
            throw PaymeException::make(PaymeException::CANNOT_PERFORM);
        }

        return $result;
    }

    /** id, reason → {transaction, cancel_time, state} | -31003 | -31007 */
    private function cancelTransaction(array $params): array
    {
        $paymeId = $this->idParam($params);
        $reason = $params['reason'] ?? null;
        if (! is_int($reason)) {
            throw PaymeException::make(PaymeException::INVALID_REQUEST);
        }
        if (! $this->transaction($paymeId)) {
            throw PaymeException::make(PaymeException::TRANSACTION_NOT_FOUND);
        }

        return DB::transaction(function () use ($paymeId, $reason) {
            $transaction = PaymentTransaction::where('provider', PaymentOrder::PROVIDER_PAYME)
                ->where('provider_transaction_id', $paymeId)
                ->lockForUpdate()
                ->first();

            if ($transaction->state === PaymentTransaction::STATE_CREATED) {
                $transaction->forceFill([
                    'state' => PaymentTransaction::STATE_CANCELLED,
                    'cancel_time' => PaymentTransaction::nowMs(),
                    'reason' => $reason,
                ])->save();
                $this->syncPayment($transaction->order, 'cancelled');
            } elseif ($transaction->state === PaymentTransaction::STATE_PERFORMED) {
                $order = $transaction->order;
                if ($this->fulfilment->reverseProblem($order) !== null) {
                    throw PaymeException::make(PaymeException::CANNOT_CANCEL);
                }
                try {
                    $this->fulfilment->reverse($order, "Payme: bekor qilindi (sabab {$reason})");
                } catch (\DomainException) {
                    throw PaymeException::make(PaymeException::CANNOT_CANCEL);
                }
                $transaction->forceFill([
                    'state' => PaymentTransaction::STATE_CANCELLED_AFTER_PERFORM,
                    'cancel_time' => PaymentTransaction::nowMs(),
                    'reason' => $reason,
                ])->save();
            }
            // -1 / -2: already cancelled — same answer as the first time

            return [
                'transaction' => (string) $transaction->id,
                'cancel_time' => $transaction->cancel_time,
                'state' => $transaction->state,
            ];
        });
    }

    /** id → {create_time, perform_time, cancel_time, transaction, state, reason} | -31003 */
    private function checkTransaction(array $params): array
    {
        $transaction = $this->transaction($this->idParam($params));
        if (! $transaction) {
            throw PaymeException::make(PaymeException::TRANSACTION_NOT_FOUND);
        }

        return [
            'create_time' => $transaction->create_time,
            'perform_time' => $transaction->perform_time,
            'cancel_time' => $transaction->cancel_time,
            'transaction' => (string) $transaction->id,
            'state' => $transaction->state,
            'reason' => $transaction->reason,
        ];
    }

    /** from, to → {transactions: [...]} ordered by Payme create time */
    private function getStatement(array $params): array
    {
        $from = $params['from'] ?? null;
        $to = $params['to'] ?? null;
        if (! is_int($from) || ! is_int($to)) {
            throw PaymeException::make(PaymeException::INVALID_REQUEST);
        }

        $transactions = PaymentTransaction::where('provider', PaymentOrder::PROVIDER_PAYME)
            ->whereBetween('provider_time', [$from, $to])
            ->orderBy('provider_time')
            ->get()
            ->map(fn (PaymentTransaction $t) => [
                'id' => $t->provider_transaction_id,
                'time' => $t->provider_time,
                'amount' => $t->amount_tiyin,
                'account' => $t->account ?? [config('payments.payme.account_field') => (string) $t->payment_order_id],
                'create_time' => $t->create_time,
                'perform_time' => $t->perform_time,
                'cancel_time' => $t->cancel_time,
                'transaction' => (string) $t->id,
                'state' => $t->state,
                'reason' => $t->reason,
            ])
            ->values()
            ->all();

        return ['transactions' => $transactions];
    }

    /**
     * Fiscal receipt data after the receipt was registered (optional method).
     * Errors here use the codes from the SetFiscalData page: -32001 not found, -32602 invalid params.
     */
    private function setFiscalData(array $params): array
    {
        $type = $params['type'] ?? null;
        if (! is_string($params['id'] ?? null) || ! in_array($type, ['PERFORM', 'CANCEL'], true) || ! is_array($params['fiscal_data'] ?? null)) {
            throw PaymeException::custom(-32602, 'Invalid params');
        }
        $transaction = $this->transaction($params['id']);
        if (! $transaction) {
            throw PaymeException::custom(-32001, 'Check not found');
        }

        $meta = $transaction->meta ?? [];
        $meta['fiscal'][$type === 'PERFORM' ? 'perform_data' : 'cancel_data'] = $params['fiscal_data'];
        $transaction->forceFill(['meta' => $meta])->save();

        return ['success' => true];
    }

    // ---------------------------------------------------------------- helpers

    private function authorize(Request $request): void
    {
        if (config('payments.payme.ip_check') && ! in_array($request->ip(), config('payments.payme.allowed_ips', []), true)) {
            throw PaymeException::make(PaymeException::INSUFFICIENT_PRIVILEGE);
        }

        $key = self::activeKey();
        $header = (string) $request->header('Authorization', '');
        if (! filled($key) || ! str_starts_with($header, 'Basic ')) {
            throw PaymeException::make(PaymeException::INSUFFICIENT_PRIVILEGE);
        }

        $decoded = base64_decode(substr($header, 6), true);
        $expected = config('payments.payme.login', 'Paycom').':'.$key;
        if ($decoded === false || ! hash_equals($expected, $decoded)) {
            throw PaymeException::make(PaymeException::INSUFFICIENT_PRIVILEGE);
        }
    }

    private function idParam(array $params): string
    {
        $id = $params['id'] ?? null;
        if (! is_string($id) || $id === '' || strlen($id) > 64) {
            throw PaymeException::make(PaymeException::INVALID_REQUEST);
        }

        return $id;
    }

    private function amountParam(array $params): int
    {
        $amount = $params['amount'] ?? null;
        if (is_float($amount) && floor($amount) === $amount) {
            $amount = (int) $amount;
        }
        if (! is_int($amount)) {
            throw PaymeException::make(PaymeException::INVALID_REQUEST);
        }
        if ($amount <= 0) {
            throw PaymeException::make(PaymeException::INVALID_AMOUNT);
        }

        return $amount;
    }

    private function orderFromAccount(array $params): PaymentOrder
    {
        $field = config('payments.payme.account_field', 'order_id');
        $account = $params['account'] ?? null;
        if (! is_array($account)) {
            throw PaymeException::make(PaymeException::INVALID_REQUEST);
        }

        $value = $account[$field] ?? null;
        $order = (is_int($value) || (is_string($value) && ctype_digit($value)))
            ? PaymentOrder::where('id', (int) $value)->where('provider', PaymentOrder::PROVIDER_PAYME)->first()
            : null;

        if (! $order) {
            throw PaymeException::make(PaymeException::ORDER_NOT_FOUND, $field);
        }

        return $order;
    }

    private function assertPayable(PaymentOrder $order): void
    {
        $field = config('payments.payme.account_field', 'order_id');
        if (! $order->isPending()) {
            throw PaymeException::make(PaymeException::ORDER_NOT_PAYABLE, $field);
        }
        if ($this->products->problem($order) !== null) {
            throw PaymeException::make(PaymeException::PRODUCT_UNAVAILABLE, $field);
        }
    }

    private function assertAmount(PaymentOrder $order, int $amount): void
    {
        if ($amount !== (int) $order->amount_tiyin) {
            throw PaymeException::make(PaymeException::INVALID_AMOUNT);
        }
    }

    private function transaction(string $paymeId): ?PaymentTransaction
    {
        return PaymentTransaction::where('provider', PaymentOrder::PROVIDER_PAYME)
            ->where('provider_transaction_id', $paymeId)
            ->first();
    }

    /** CreateTransaction for an id we already have: same answer, or -31008 if it is no longer state 1. */
    private function repeatCreate(PaymentTransaction $transaction): array
    {
        if ($transaction->state !== PaymentTransaction::STATE_CREATED) {
            throw PaymeException::make(PaymeException::CANNOT_PERFORM);
        }
        if ($this->timedOut($transaction)) {
            DB::transaction(fn () => $this->cancelByTimeout(
                PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->first()
            ));
            throw PaymeException::make(PaymeException::CANNOT_PERFORM);
        }

        return $this->createResult($transaction);
    }

    private function createResult(PaymentTransaction $transaction): array
    {
        return [
            'create_time' => $transaction->create_time,
            'transaction' => (string) $transaction->id,
            'state' => $transaction->state,
        ];
    }

    private function performResult(PaymentTransaction $transaction): array
    {
        return [
            'transaction' => (string) $transaction->id,
            'perform_time' => $transaction->perform_time,
            'state' => $transaction->state,
        ];
    }

    /** 12 hours after the transaction was created in Payme Business. */
    private function timedOut(PaymentTransaction $transaction): bool
    {
        $created = $transaction->provider_time ?: $transaction->create_time;

        return PaymentTransaction::nowMs() - $created > (int) config('payments.payme.timeout_ms', 43_200_000);
    }

    private function cancelByTimeout(PaymentTransaction $transaction): void
    {
        if ($transaction->state !== PaymentTransaction::STATE_CREATED) {
            return;
        }

        $transaction->forceFill([
            'state' => PaymentTransaction::STATE_CANCELLED,
            'cancel_time' => PaymentTransaction::nowMs(),
            'reason' => PaymentTransaction::REASON_TIMEOUT,
        ])->save();
        $this->syncPayment($transaction->order, 'cancelled');
    }

    /** Keep the payments ledger row (shown in the admin) in step with the provider. */
    private function syncPayment(?PaymentOrder $order, string $status, ?string $providerId = null): void
    {
        if (! $order?->payment_id || $order->isPaid()) {
            return;
        }

        Payment::whereKey($order->payment_id)->whereIn('status', ['pending', 'processing', 'cancelled'])->update(array_filter([
            'status' => $status,
            'provider_transaction_id' => $providerId,
        ]));
    }

    /** CheckPerformTransaction.detail for the fiscal receipt, when an IKPU code is configured. */
    private function fiscalDetail(PaymentOrder $order): ?array
    {
        $fiscal = config('payments.payme.fiscal', []);
        if (! filled($fiscal['ikpu'] ?? null)) {
            return null;
        }

        return [
            'receipt_type' => 0,
            'items' => [[
                'title' => mb_substr($order->title, 0, 120),
                'price' => (int) $order->amount_tiyin,
                'count' => 1,
                'code' => (string) $fiscal['ikpu'],
                'package_code' => (string) ($fiscal['package_code'] ?? ''),
                'vat_percent' => (int) ($fiscal['vat_percent'] ?? 0),
            ]],
        ];
    }
}
