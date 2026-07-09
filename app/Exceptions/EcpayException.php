<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown when an ECPay gateway call cannot be trusted or completed —
 * signature verification failure, a network/HTTP failure, or the gateway
 * itself rejecting the request. `$reason` is a machine code that boundaries
 * translate via lang/{locale}/orders.php -> payment_errors.{reason}, mirroring
 * CouponException's shape.
 *
 * Defines render() so Laravel's exception handler turns this into a flashed
 * error automatically — callers (OrderController::cancel, Seller\OrderController
 * cancel/approveCancellation/approveReturn) don't need their own try/catch.
 */
class EcpayException extends Exception
{
    public function __construct(public readonly string $reason, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $reason);
    }

    public function translatedMessage(): string
    {
        return __("orders.payment_errors.{$this->reason}");
    }

    public function render(Request $request): RedirectResponse
    {
        return back()->with('error', $this->translatedMessage());
    }
}
