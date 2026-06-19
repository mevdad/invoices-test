<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $number
 * @property string $supplier_name
 * @property string $supplier_tax_id
 * @property string $net_amount
 * @property string $vat_amount
 * @property string $gross_amount
 * @property string $currency
 * @property InvoiceStatus $status
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number',
    'supplier_name',
    'supplier_tax_id',
    'net_amount',
    'vat_amount',
    'gross_amount',
    'currency',
    'status',
    'issue_date',
    'due_date',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, HasUuids;

    /**
     * Determine whether the invoice can still be edited.
     */
    public function isPending(): bool
    {
        return $this->status === InvoiceStatus::Pending;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'net_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }
}
