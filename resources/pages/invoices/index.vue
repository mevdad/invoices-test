<script setup lang="ts">
import type { Invoice, PaginatedResponse } from '~/types/invoice';

const api = useInvoiceApi();
const route = useRoute();

const page = computed(() => Math.max(1, Number(route.query.page) || 1));

const { data, status, error, refresh } = await useAsyncData<PaginatedResponse<Invoice>>(
    'invoices',
    () => api.list(page.value),
    { watch: [page] },
);

const invoices = computed(() => data.value?.data ?? []);
const meta = computed(() => data.value?.meta);
// Skeleton only on the very first load — keep the table during page changes.
const isInitialLoading = computed(() => status.value === 'pending' && !data.value);

// If the requested page is past the last one (e.g. ?page=999), clamp to the
// last real page instead of showing an empty list. Runs on SSR/initial load and
// again whenever the data changes on the client.
function clampPageIfOutOfRange(): Promise<unknown> | void {
    const m = data.value?.meta;

    if (m && m.total > 0 && m.current_page > m.last_page) {
        navigateTo({ query: { page: m.last_page }, replace: true });
    }
}

await clampPageIfOutOfRange();
watch(data, () => clampPageIfOutOfRange());

// Page list with first/last always shown and ellipses for the gaps,
// e.g. 1 … 4 5 [6] 7 8 … 20
type PageItem = number | 'left-ellipsis' | 'right-ellipsis';

const paginationItems = computed<PageItem[]>(() => {
    if (!meta.value) {
        return [];
    }

    const { current_page: current, last_page: last } = meta.value;
    const delta = 1; // neighbours on each side of the current page
    const left = Math.max(2, current - delta);
    const right = Math.min(last - 1, current + delta);
    const items: PageItem[] = [1];

    if (left > 2) {
        items.push('left-ellipsis');
    }

    for (let i = left; i <= right; i += 1) {
        items.push(i);
    }

    if (right < last - 1) {
        items.push('right-ellipsis');
    }

    if (last > 1) {
        items.push(last);
    }

    return items;
});

useSeoMeta({ title: 'Invoices' });

function openInvoice(id: string): void {
    navigateTo(`/invoices/${id}`);
}

function goToPage(target: number): void {
    navigateTo({ query: { page: target } });
}
</script>

<template>
    <section>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Invoices</h1>
            <AppButton to="/invoices/create">New invoice</AppButton>
        </div>

        <!-- Loading skeleton -->
        <div v-if="isInitialLoading" class="space-y-2" aria-busy="true">
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
            <AppButton variant="danger" class="mt-3" @click="refresh()">Try again</AppButton>
        </div>

        <!-- Empty state — only when the database is genuinely empty, not when an
             out-of-range page is being clamped. -->
        <div
            v-else-if="(meta?.total ?? 0) === 0"
            class="rounded-md border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500"
        >
            No invoices yet.
        </div>

        <!-- Data -->
        <div v-else>
        <div
            class="overflow-hidden rounded-lg border border-gray-200 bg-white transition-opacity"
            :class="{ 'opacity-60': status === 'pending' }"
        >
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

        <!-- Pagination -->
        <div
            v-if="meta && meta.last_page > 1"
            class="mt-4 flex items-center justify-between text-sm text-gray-600"
        >
            <p>Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}</p>
            <div class="flex items-center gap-1">
                <AppButton
                    variant="outline"
                    size="sm"
                    :disabled="meta.current_page <= 1 || status === 'pending'"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Prev
                </AppButton>

                <template v-for="item in paginationItems" :key="item">
                    <span
                        v-if="item === 'left-ellipsis' || item === 'right-ellipsis'"
                        class="min-w-9 px-2 py-1.5 text-center text-gray-400"
                    >
                        …
                    </span>
                    <AppButton
                        v-else
                        size="sm"
                        :variant="item === meta.current_page ? 'primary' : 'outline'"
                        :disabled="status === 'pending'"
                        @click="goToPage(item)"
                    >
                        {{ item }}
                    </AppButton>
                </template>

                <AppButton
                    variant="outline"
                    size="sm"
                    :disabled="meta.current_page >= meta.last_page || status === 'pending'"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Next
                </AppButton>
            </div>
        </div>
        </div>
    </section>
</template>
