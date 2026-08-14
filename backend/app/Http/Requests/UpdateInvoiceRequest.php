<?php

namespace App\Http\Requests;

use App\Support\MoneyNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // According to the technical specifications, editing is only allowed when the status is "pending."
        $invoice = $this->route('invoice');
        return $invoice && $invoice->status === 'pending';
    }

    protected function prepareForValidation(): void
    {
        // Never Trust Client Input: We only accept net_amount and vat_amount, calculating gross_amount HERE
        if ($this->filled(['net_amount', 'vat_amount'])) {
            $this->merge([
                'net_amount'   => MoneyNormalizer::format($this->net_amount),
                'vat_amount'   => MoneyNormalizer::format($this->vat_amount),
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
            'due_date' => 'required|date|after_or_equal:issue_date',
        ];
    }
}
