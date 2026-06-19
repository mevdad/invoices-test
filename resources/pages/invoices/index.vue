<script setup lang="ts">
import type { Invoice, PaginatedResponse } from '~/types/invoice';

const api = useInvoiceApi();

const { data, status, error, refresh } = await useAsyncData<PaginatedResponse<Invoice>>(
    'invoices',
    () => api.list(),
);

const invoices = computed(() => data.value?.data ?? []);
const isLoading = computed(() => status.value === 'pending');

useSeoMeta({ title: 'Invoices' });

function openInvoice(id: string): void {
    navigateTo(`/invoices/${id}`);
}
</script>

<template>
    <section>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Invoices</h1>
            <NuxtLink
                to="/invoices/create"
                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
            >
                New invoice
            </NuxtLink>
        </div>

        <!-- Loading skeleton -->
        <div v-if="isLoading" class="space-y-2" aria-busy="true">
            <div
                v-for="n in 6"
                :key="n"
                class="h-12 animate-pulse rounded-md bg-gray-200"
            />
        </div>

        <!-- Error state -->
        <div
            v-else-if="error"
            class="rounded-md border border-red-200 bg-red-50 p-6 text-center"
        >
            <p class="text-sm text-red-700">Failed to load invoices.</p>
            <button
                type="button"
                class="mt-3 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500"
                @click="refresh()"
            >
                Try again
            </button>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="invoices.length === 0"
            class="rounded-md border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500"
        >
            No invoices yet.
        </div>

        <!-- Data -->
        <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Number</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Gross</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Due date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="cursor-pointer hover:bg-gray-50"
                        @click="openInvoice(invoice.id)"
                    >
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ invoice.number }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ invoice.supplier_name }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-gray-900">
                            {{ formatMoney(invoice.gross_amount, invoice.currency) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <StatusBadge :status="invoice.status" />
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ formatDate(invoice.due_date) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
