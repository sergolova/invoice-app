<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $invoices = Invoice::orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $invoices]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] = 'pending'; // Rewrite everything the client was able to send
        $invoice = Invoice::create($data);

        return response()->json(['data' => $invoice], Response::HTTP_CREATED);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['data' => $invoice]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        if ($request->filled('updated_at')) {
            // ! Potential bug in Prod: Optimistic locking and microseconds
            $clientUpdatedAt = Carbon::parse($request->input('updated_at'))->timestamp;
            $serverUpdatedAt = $invoice->updated_at->timestamp;

            if ($clientUpdatedAt !== $serverUpdatedAt) {
                return response()->json([
                    'message' => 'Рахунок було змінено іншим користувачем. Оновіть сторінку, щоб отримати актуальні дані.'
                ], Response::HTTP_CONFLICT);
            }
        }

        $invoice->update($request->validated());

        // Forcibly re-reads the latest data from PostgreSQL into the model instance
        $invoice->refresh();

        return response()->json(['data' => $invoice]);
    }
}
