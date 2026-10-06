<?php

use App\Http\Controllers\Webhooks\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/paystack', PaystackWebhookController::class)->name('paystack');
