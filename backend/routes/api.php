<?php

use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::apiResource('invoices', InvoiceController::class)->except(['destroy']);
