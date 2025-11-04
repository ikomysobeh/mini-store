<?php
use Illuminate\Support\Facades\Route;


Route::post('/stripe/webhook', [\App\Http\Controllers\Web\StripeWebhookController::class, 'handle'])->name('stripe.webhook');

