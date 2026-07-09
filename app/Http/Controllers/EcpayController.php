<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EcpayController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * ECPay's server-to-server payment notify (ReturnURL). Must reply with the
     * exact plain-text "1|OK" on success or ECPay will keep retrying; any
     * other body/status is treated as a failure by ECPay.
     */
    public function notify(Request $request): Response
    {
        $accepted = $this->paymentService->handleGatewayNotification($request->all());

        return response($accepted ? '1|OK' : '0|Fail');
    }
}
