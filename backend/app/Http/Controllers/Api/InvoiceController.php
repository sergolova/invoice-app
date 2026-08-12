<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $invoices = Invoice::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $invoices]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = Invoice::create($request->validated());
        return response()->json(['data' => $invoice], 210);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['data' => $invoice]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $invoice->update($request->validated());
        return response()->json(['data' => $invoice]);
    }
}
