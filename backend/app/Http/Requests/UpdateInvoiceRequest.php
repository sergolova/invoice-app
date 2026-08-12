<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // According to the technical specifications, editing is only allowed when the status is "pending."
        $invoice = $this->route('invoice');
        return $invoice && $invoice->status === 'pending';
    }

    public function rules(): array
    {
        return [
            'net_amount' => 'required|numeric|gt:0',
            'vat_amount' => 'required|numeric|gte:0',
            'due_date' => 'required|date|after_or_equal:issue_date',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Automatically recalculate `gross_amount` when updating
        if ($this->has(['net_amount', 'vat_amount'])) {
            $this->merge([
                'gross_amount' => (float)$this->net_amount + (float)$this->vat_amount,
            ]);
        }
    }
}
