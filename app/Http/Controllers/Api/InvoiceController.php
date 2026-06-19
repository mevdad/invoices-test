<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    /**
     * List invoices, newest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return InvoiceResource::collection(
            Invoice::latest()->paginate(15)
        );
    }

    /**
     * Show a single invoice.
     */
    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice);
    }

    /**
     * Create a new invoice.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoices->create($request->validated());

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    /**
     * Update an invoice. The non-pending guard runs in UpdateInvoiceRequest
     * before validation, so reaching here means the invoice is editable.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $invoice = $this->invoices->update($invoice, $request->validated());

        return new InvoiceResource($invoice);
    }

    /**
     * Approve a pending invoice.
     */
    public function approve(Invoice $invoice): InvoiceResource
    {
        abort_unless($invoice->isPending(), HttpResponse::HTTP_CONFLICT, 'Only pending invoices can be approved.');

        $invoice = $this->invoices->approve($invoice);

        return new InvoiceResource($invoice);
    }
}
