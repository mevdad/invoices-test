<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class InvoiceService
{
    /**
     * Create a new invoice, deriving the gross amount server-side.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Invoice
    {
        $data['status'] ??= InvoiceStatus::Pending;
        $data['gross_amount'] = $this->grossAmount($data['net_amount'], $data['vat_amount']);

        return Invoice::create($data);
    }

    /**
     * Update an editable invoice, recomputing the gross amount from the new figures.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $data['gross_amount'] = $this->grossAmount($data['net_amount'], $data['vat_amount']);

        $invoice->update($data);

        return $invoice->refresh();
    }

    /**
     * Approve a pending invoice.
     */
    public function approve(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::Approved]);

        return $invoice->refresh();
    }

    /**
     * Gross is always derived from net + vat — the single source of truth.
     */
    private function grossAmount(int|float|string $netAmount, int|float|string $vatAmount): float
    {
        return round((float) $netAmount + (float) $vatAmount, 2);
    }
}
