<?php

namespace App\Http\Requests;

use App\Support\MoneyNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // According to the technical specifications, editing is only allowed when the status is "pending."
        $invoice = $this->route('invoice');

        return $invoice && $invoice->status === 'pending';
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Редагування доступне лише для статусу "Очікує"'
            ], Response::HTTP_FORBIDDEN) // 403
        );
    }

    protected function prepareForValidation(): void
    {
        $invoice = $this->route('invoice');

        if ($invoice) {
            $this->mergeIfMissing([
                'issue_date' => $invoice->issue_date?->format('Y-m-d'),
            ]);
        }

        // Never Trust Client Input: We only accept net_amount and vat_amount, calculating gross_amount HERE
        if ($this->filled(['net_amount', 'vat_amount'])) {
            $this->merge([
                'net_amount'   => MoneyNormalizer::normalize($this->net_amount),
                'vat_amount'   => MoneyNormalizer::normalize($this->vat_amount),
                'gross_amount' => MoneyNormalizer::sum($this->net_amount, $this->vat_amount),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'net_amount'   => 'required|numeric|gt:0|max:9999999999.99',
            'vat_amount'   => 'required|numeric|gte:0|max:9999999999.99',
            'gross_amount' => 'sometimes|numeric|max:9999999999.99',
            'due_date'     => 'required|date|after_or_equal:issue_date',
        ];
    }
}
