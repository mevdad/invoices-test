<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_invoices_newest_first(): void
    {
        $older = Invoice::factory()->create(['created_at' => now()->subDay()]);
        $newer = Invoice::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/invoices');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    public function test_show_returns_a_single_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $this->getJson("/api/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.number', $invoice->number);
    }

    public function test_show_returns_404_for_unknown_invoice(): void
    {
        $this->getJson('/api/invoices/'.fake()->uuid())
            ->assertNotFound();
    }

    public function test_store_creates_an_invoice_and_derives_gross_amount(): void
    {
        $payload = [
            'number' => 'INV-12345',
            'supplier_name' => 'Acme LLC',
            'supplier_tax_id' => '1234567890',
            'net_amount' => 1000,
            'vat_amount' => 200,
            'gross_amount' => 999999, // should be ignored / recomputed
            'currency' => 'UAH',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-02-01',
        ];

        $response = $this->postJson('/api/invoices', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.gross_amount', '1200.00')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('invoices', [
            'number' => 'INV-12345',
            'gross_amount' => 1200.00,
        ]);
    }

    public function test_store_requires_a_unique_number(): void
    {
        Invoice::factory()->create(['number' => 'INV-DUP']);

        $this->postJson('/api/invoices', $this->validPayload(['number' => 'INV-DUP']))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('number');
    }

    public function test_store_requires_a_number(): void
    {
        $payload = $this->validPayload();
        unset($payload['number']);

        $this->postJson('/api/invoices', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('number');
    }

    public function test_store_rejects_non_positive_net_amount(): void
    {
        $this->postJson('/api/invoices', $this->validPayload(['net_amount' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('net_amount');
    }

    public function test_store_rejects_negative_vat_amount(): void
    {
        $this->postJson('/api/invoices', $this->validPayload(['vat_amount' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('vat_amount');
    }

    public function test_store_rejects_due_date_before_issue_date(): void
    {
        $this->postJson('/api/invoices', $this->validPayload([
            'issue_date' => '2026-02-01',
            'due_date' => '2026-01-01',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('due_date');
    }

    public function test_update_recomputes_gross_for_a_pending_invoice(): void
    {
        $invoice = Invoice::factory()->pending()->create([
            'issue_date' => '2026-01-01',
        ]);

        $this->putJson("/api/invoices/{$invoice->id}", [
            'net_amount' => 500,
            'vat_amount' => 100,
            'due_date' => '2026-03-01',
        ])
            ->assertOk()
            ->assertJsonPath('data.gross_amount', '600.00')
            ->assertJsonPath('data.due_date', '2026-03-01');
    }

    public function test_update_is_blocked_for_non_pending_invoices(): void
    {
        $invoice = Invoice::factory()->approved()->create();

        $this->putJson("/api/invoices/{$invoice->id}", [
            'net_amount' => 500,
            'vat_amount' => 100,
            'due_date' => '2026-03-01',
        ])
            ->assertStatus(409);
    }

    public function test_update_revalidates_due_date_against_issue_date(): void
    {
        $invoice = Invoice::factory()->pending()->create([
            'issue_date' => '2026-02-01',
        ]);

        $this->putJson("/api/invoices/{$invoice->id}", [
            'net_amount' => 500,
            'vat_amount' => 100,
            'due_date' => '2026-01-01',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('due_date');
    }

    public function test_approve_transitions_a_pending_invoice(): void
    {
        $invoice = Invoice::factory()->pending()->create();

        $this->postJson("/api/invoices/{$invoice->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'approved',
        ]);
    }

    public function test_approve_is_blocked_for_non_pending_invoices(): void
    {
        $invoice = Invoice::factory()->approved()->create();

        $this->postJson("/api/invoices/{$invoice->id}/approve")
            ->assertStatus(409);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'supplier_name' => 'Acme LLC',
            'supplier_tax_id' => '1234567890',
            'net_amount' => 1000,
            'vat_amount' => 200,
            'currency' => 'UAH',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-02-01',
        ], $overrides);
    }
}
