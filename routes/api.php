<?php

use App\Http\Controllers\Api\V1Controller;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::get('escalas', [V1Controller::class, 'schedules']);
    Route::get('escalas/{schedule}/plantoes', [V1Controller::class, 'shifts']);
    Route::get('profissionais', [V1Controller::class, 'professionals']);
    Route::get('checkins', [V1Controller::class, 'checkins']);
});

Route::post('nfse/webhook', function () {
    $payload = request()->all();
    $service = app(\App\Services\NfseService::class);
    $invoice = $service->handleWebhook($payload);

    return response()->json([
        'received' => true,
        'invoice_id' => $invoice?->id,
        'status' => $invoice?->status,
    ]);
});
