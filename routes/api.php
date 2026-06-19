<?php

use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])
    ->name('invoices.approve');

Route::apiResource('invoices', InvoiceController::class)
    ->only(['index', 'show', 'store', 'update']);
