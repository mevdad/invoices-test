<script setup lang="ts">
import type { Invoice, ResourceResponse } from '~/types/invoice';

const route = useRoute();
const id = route.params.id as string;
const api = useInvoiceApi();

const { data, status, error } = await useAsyncData<ResourceResponse<Invoice>>(
    `invoice-${id}`,
    () => api.get(id),
);

// Surface backend 404 (or any load failure) as a Nuxt error page, rendered
// server-side during SSR.
if (error.value) {
    const statusCode = (error.value as { statusCode?: number }).statusCode ?? 500;

    throw createError({
        statusCode: statusCode === 404 ? 404 : 500,
        statusMessage: statusCode === 404 ? 'Invoice not found' : 'Failed to load invoice',
        fatal: true,
    });
}

const invoice = computed(() => data.value?.data);
const isLoading = computed(() => status.value === 'pending');

useSeoMeta({
    title: () => (invoice.value ? `Invoice ${invoice.value.number}` : 'Invoice'),
});

function setInvoice(updated: Invoice): void {
    // The mutation response already contains the fresh invoice, so update the
    // local data in place. Avoid refresh() — it would flip isLoading, swap the
    // form for the skeleton, and unmount any success message.
    if (data.value) {
        data.value = { data: updated };
    }
}

function onUpdated(updated: Invoice): void {
    setInvoice(updated);
}

const isApproving = ref(false);
const approveError = ref<string | null>(null);
const approved = ref(false);

async function approveInvoice(): Promise<void> {
    if (!invoice.value) {
        return;
    }

    isApproving.value = true;
    approveError.value = null;

    try {
        const { data: updated } = await api.approve(invoice.value.id);
        setInvoice(updated);
        approved.value = true;
    } catch (e) {
        const err = e as { statusCode?: number; data?: { message?: string } };
        approveError.value =
            err.statusCode === 409
                ? (err.data?.message ?? 'This invoice can no longer be approved.')
                : 'Something went wrong while approving. Please try again.';
    } finally {
        isApproving.value = false;
    }
}
</script>

<template>
    <section>
        <NuxtLink to="/invoices" class="text-sm text-gray-500 hover:text-gray-700">
            ← Back to invoices
        </NuxtLink>

        <!-- Loading skeleton (client-side navigation, only when no data yet) -->
        <div v-if="isLoading && !invoice" class="mt-4 space-y-3" aria-busy="true">
            <div class="h-8 w-48 animate-pulse rounded bg-gray-200" />
            <div class="h-40 animate-pulse rounded-lg bg-gray-200" />
        </div>

        <template v-else-if="invoice">
            <div class="mt-4 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">{{ invoice.number }}</h1>
                    <p class="text-sm text-gray-500">{{ invoice.supplier_name }}</p>
                </div>
                <StatusBadge :status="invoice.status" />
            </div>

            <!-- Summary -->
            <dl class="mt-6 grid grid-cols-1 gap-x-8 gap-y-4 rounded-lg border border-gray-200 bg-white p-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Supplier tax ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ invoice.supplier_tax_id }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Currency</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ invoice.currency }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Net amount</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatMoney(invoice.net_amount, invoice.currency) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">VAT amount</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatMoney(invoice.vat_amount, invoice.currency) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Gross amount</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ formatMoney(invoice.gross_amount, invoice.currency) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Issue date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invoice.issue_date) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Due date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invoice.due_date) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Last updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(invoice.updated_at) }}</dd>
                </div>
            </dl>

            <!-- Edit form -->
            <div class="mt-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-base font-semibold text-gray-900">Edit invoice</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Update the financial figures. Gross is recalculated automatically.
                </p>
                <Transition name="fade">
                    <div
                        v-if="approved"
                        class="mt-4 flex items-center gap-2 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.5 7.6a1 1 0 0 1-1.42.004l-3.5-3.5a1 1 0 1 1 1.414-1.414l2.79 2.79 6.796-6.888a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                        </svg>
                        Invoice approved successfully.
                    </div>
                </Transition>

                <Transition name="fade">
                    <div
                        v-if="approveError"
                        class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {{ approveError }}
                    </div>
                </Transition>

                <div class="mt-5">
                    <InvoiceEditForm
                        :invoice="invoice"
                        :is-approving="isApproving"
                        @updated="onUpdated"
                        @approve="approveInvoice"
                    />
                </div>
            </div>
        </template>
    </section>
</template>
