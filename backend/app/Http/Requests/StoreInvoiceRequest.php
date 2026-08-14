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
        // There can't be a "status" key here, because that would allow you to create an "approved" account right away.
        // "pending" status is default for column
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
                    $expectedGross = bcadd($this->input('net_amount'), $this->input('vat_amount'), 2);
                    if (bccomp((string) $value, $expectedGross, 2) !== 0) {
                        $fail('Gross amount must equal Net amount + VAT amount.');
                    }
                },
            ],
            'currency' => 'required|string|size:3',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date'
        ];
    }
}
