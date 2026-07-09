<?php

use App\Http\Controllers\EcpayController;
use App\Http\Controllers\LangController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/component-lang/{name}', [LangController::class, 'getComponents']);

// ECPay server-to-server payment notify. Lives on the `api` group (no CSRF,
// no session auth) since it's called by ECPay's servers, not a logged-in user.
Route::post('/payments/ecpay/notify', [EcpayController::class, 'notify'])->name('api.payments.ecpay.notify');
