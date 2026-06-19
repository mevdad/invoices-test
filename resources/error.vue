<script setup lang="ts">
import type { NuxtError } from '#app';

const props = defineProps<{ error: NuxtError }>();

const isNotFound = computed(() => props.error.statusCode === 404);

function handleClear(): void {
    clearError({ redirect: '/invoices' });
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 text-center">
        <p class="text-5xl font-bold text-gray-900">{{ error.statusCode }}</p>
        <h1 class="mt-4 text-lg font-medium text-gray-700">
            {{ isNotFound ? 'Invoice not found' : 'Something went wrong' }}
        </h1>
        <p class="mt-2 max-w-md text-sm text-gray-500">
            {{ isNotFound
                ? 'The invoice you are looking for does not exist or may have been removed.'
                : 'An unexpected error occurred while loading this page.' }}
        </p>
        <button
            type="button"
            class="mt-6 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
            @click="handleClear"
        >
            Back to invoices
        </button>
    </div>
</template>
