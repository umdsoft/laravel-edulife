<?php

namespace App\Services\Payments;

/**
 * A Merchant API error returned to Payme as {"error": {code, message, data}}.
 * https://developer.help.paycom.uz/protokol-merchant-api/format-otveta
 */
class PaymeException extends \RuntimeException
{
    // common errors — https://developer.help.paycom.uz/protokol-merchant-api/obschie-oshibki
    public const NOT_POST = -32300;
    public const PARSE_ERROR = -32700;
    public const INVALID_REQUEST = -32600;
    public const METHOD_NOT_FOUND = -32601;
    public const INSUFFICIENT_PRIVILEGE = -32504;
    public const SYSTEM_ERROR = -32400;

    // merchant errors — https://developer.help.paycom.uz/metody-merchant-api/oshibki-errors
    public const INVALID_AMOUNT = -31001;
    public const TRANSACTION_NOT_FOUND = -31003;
    public const CANNOT_CANCEL = -31007;
    public const CANNOT_PERFORM = -31008;

    // -31050 … -31099: wrong "account" (localized message required, data = account field)
    public const ORDER_NOT_FOUND = -31050;
    public const ORDER_NOT_PAYABLE = -31051;
    public const PRODUCT_UNAVAILABLE = -31052;

    private const MESSAGES = [
        self::NOT_POST => ["So'rov POST emas", 'Метод запроса не POST', 'Request method is not POST'],
        self::PARSE_ERROR => ['JSON xato', 'Ошибка парсинга JSON', 'JSON parse error'],
        self::INVALID_REQUEST => ["So'rov maydonlari noto'g'ri", 'Неверный RPC-запрос', 'Invalid RPC request'],
        self::METHOD_NOT_FOUND => ['Metod topilmadi', 'Метод не найден', 'Method not found'],
        self::INSUFFICIENT_PRIVILEGE => ["Ruxsat yo'q", 'Недостаточно привилегий для выполнения метода', 'Insufficient privilege to perform this method'],
        self::SYSTEM_ERROR => ['Tizim xatosi', 'Системная ошибка', 'System error'],
        self::INVALID_AMOUNT => ["Summa noto'g'ri", 'Неверная сумма', 'Invalid amount'],
        self::TRANSACTION_NOT_FOUND => ['Tranzaksiya topilmadi', 'Транзакция не найдена', 'Transaction not found'],
        self::CANNOT_CANCEL => ["Buyurtma bajarilgan, tranzaksiyani bekor qilib bo'lmaydi", 'Заказ выполнен. Невозможно отменить транзакцию', 'Order is completed, the transaction cannot be cancelled'],
        self::CANNOT_PERFORM => ["Amalni bajarib bo'lmaydi", 'Невозможно выполнить операцию', 'Unable to perform operation'],
        self::ORDER_NOT_FOUND => ['Buyurtma topilmadi', 'Заказ не найден', 'Order not found'],
        self::ORDER_NOT_PAYABLE => ["Buyurtma to'lov kutmayapti", 'Заказ не ожидает оплаты', 'Order is not awaiting payment'],
        self::PRODUCT_UNAVAILABLE => ["Mahsulotni sotib bo'lmaydi", 'Товар или услуга недоступны', 'Product or service is unavailable'],
    ];

    private ?string $plainMessage = null;

    public function __construct(int $code, private readonly mixed $data = null)
    {
        parent::__construct((string) (self::MESSAGES[$code][2] ?? 'Error'), $code);
    }

    public static function make(int $code, mixed $data = null): self
    {
        return new self($code, $data);
    }

    /** SetFiscalData errors carry a plain string message (https://developer.help.paycom.uz/metody-merchant-api/setfiscaldata). */
    public static function custom(int $code, string $message): self
    {
        $e = new self($code);
        $e->plainMessage = $message;

        return $e;
    }

    public function toArray(): array
    {
        if ($this->plainMessage !== null) {
            return ['code' => $this->getCode(), 'message' => $this->plainMessage];
        }

        [$uz, $ru, $en] = self::MESSAGES[$this->getCode()] ?? ['Xato', 'Ошибка', 'Error'];

        return array_filter([
            'code' => $this->getCode(),
            'message' => ['uz' => $uz, 'ru' => $ru, 'en' => $en],
            'data' => $this->data,
        ], fn ($v) => $v !== null);
    }
}
