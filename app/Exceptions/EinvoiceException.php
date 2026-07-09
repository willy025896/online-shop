<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when an ECPay electronic invoice (B2C 電子發票) API call cannot be
 * completed — network/HTTP failure or the gateway rejecting the request.
 * Callers (InvoiceService) catch this and log rather than let it roll back
 * the payment/refund transaction that triggered it — see ADR-019: invoice
 * issuance/void/allowance is a best-effort side effect of an already-confirmed
 * money movement, not something that should undo it.
 */
class EinvoiceException extends Exception
{
    public function __construct(public readonly string $reason, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $reason);
    }
}
