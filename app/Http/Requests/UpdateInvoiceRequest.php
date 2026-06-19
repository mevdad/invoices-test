<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reject edits to non-pending invoices before any field validation runs,
     * since their state forbids editing regardless of the payload.
     */
    protected function prepareForValidation(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        abort_unless($invoice->isPending(), Response::HTTP_CONFLICT, 'Only pending invoices can be updated.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Only the financial fields exposed by the edit form may be updated.
     * `due_date` is revalidated against the invoice's existing `issue_date`.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return [
            'net_amount' => ['required', 'numeric', 'gt:0'],
            'vat_amount' => ['required', 'numeric', 'gte:0'],
            'due_date' => ['required', 'date', 'after_or_equal:'.$invoice->issue_date->toDateString()],
        ];
    }
}
