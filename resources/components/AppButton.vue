<script setup lang="ts">
type Variant = 'primary' | 'outline' | 'danger' | 'success';
type Size = 'sm' | 'md';

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        size?: Size;
        type?: 'button' | 'submit';
        disabled?: boolean;
        loading?: boolean;
        // When set, renders a <NuxtLink> instead of a <button>.
        to?: string;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
        loading: false,
        to: undefined,
    },
);

const base =
    'inline-flex cursor-pointer items-center justify-center gap-2 rounded-md font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-60';

const sizes: Record<Size, string> = {
    sm: 'min-w-9 px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
};

const variants: Record<Variant, string> = {
    primary: 'border border-gray-900 bg-gray-900 text-white hover:bg-gray-700',
    outline: 'border border-gray-300 text-gray-700 hover:bg-gray-50',
    danger: 'bg-red-600 text-white hover:bg-red-500',
    success: 'bg-green-600 text-white hover:bg-green-500',
};

const classes = computed(() => [base, sizes[props.size], variants[props.variant]]);
const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <NuxtLink v-if="to" :to="to" :class="classes">
        <Spinner v-if="loading" class="h-4 w-4" />
        <slot />
    </NuxtLink>
    <button v-else :type="type" :disabled="isDisabled" :class="classes">
        <Spinner v-if="loading" class="h-4 w-4" />
        <slot />
    </button>
</template>
