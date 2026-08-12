<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => 'required|string|unique:invoices,number',
            'supplier_name' => 'required|string|max:255',
            'supplier_tax_id' => 'required|string|max:50',
            'net_amount' => 'required|numeric|gt:0',
            'vat_amount' => 'required|numeric|gte:0',
            'gross_amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $net = (float) $this->input('net_amount');
                    $vat = (float) $this->input('vat_amount');
                    if (abs((float)$value - ($net + $vat)) > 0.01) {
                        $fail('Gross amount must equal Net amount + VAT amount.');
                    }
                },
            ],
            'currency' => 'required|string|size:3',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'status' => 'nullable|in:pending,approved,rejected',
        ];
    }
}
