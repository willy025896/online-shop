<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a coupon cannot be applied. `$reason` is a machine code
 * (e.g. 'expired', 'min_spend') that boundaries translate via
 * lang/{locale}/coupons.php -> errors.{reason}.
 */
class CouponException extends Exception
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }

    public function translatedMessage(): string
    {
        return __("coupons.errors.{$this->reason}");
    }
}
