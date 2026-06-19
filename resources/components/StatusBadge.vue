<script setup lang="ts">
import type { InvoiceStatus } from '~/types/invoice';

const props = defineProps<{ status: InvoiceStatus }>();

const classes: Record<InvoiceStatus, string> = {
    pending: 'bg-amber-100 text-amber-800 ring-amber-600/20',
    approved: 'bg-green-100 text-green-800 ring-green-600/20',
    rejected: 'bg-red-100 text-red-800 ring-red-600/20',
};

const label = computed(
    () => props.status.charAt(0).toUpperCase() + props.status.slice(1),
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
        :class="classes[status]"
    >
        {{ label }}
    </span>
</template>
