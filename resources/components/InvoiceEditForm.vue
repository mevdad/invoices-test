<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import * as z from 'zod';
import type { Invoice } from '~/types/invoice';

const props = withDefaults(
    defineProps<{ invoice: Invoice; isApproving?: boolean }>(),
    { isApproving: false },
);
const emit = defineEmits<{ updated: [invoice: Invoice]; approve: [] }>();

const api = useInvoiceApi();

const isEditable = computed(() => props.invoice.status === 'pending');

const validationSchema = toTypedSchema(
    z
        .object({
            net_amount: z.coerce
                .number({ message: 'Net amount is required' })
                .gt(0, 'Net amount must be greater than 0'),
            vat_amount: z.coerce
                .number({ message: 'VAT amount is required' })
                .gte(0, 'VAT amount cannot be negative'),
            due_date: z.string().min(1, 'Due date is required'),
        })
        .refine((data) => data.due_date >= props.invoice.issue_date, {
            path: ['due_date'],
            message: 'Due date must be on or after the issue date',
        }),
);

const { handleSubmit, defineField, setErrors, values, errors, isSubmitting } = useForm({
    validationSchema,
    initialValues: {
        net_amount: Number(props.invoice.net_amount),
        vat_amount: Number(props.invoice.vat_amount),
        due_date: props.invoice.due_date,
    },
});

const [netAmount, netAmountAttrs] = defineField('net_amount');
const [vatAmount, vatAmountAttrs] = defineField('vat_amount');
const [dueDate, dueDateAttrs] = defineField('due_date');

// Gross is always net + vat — mirrors the server's single source of truth.
const grossAmount = computed(
    () => Number(values.net_amount || 0) + Number(values.vat_amount || 0),
);

const formError = ref<string | null>(null);
const success = ref(false);

const onSubmit = handleSubmit(async (formValues) => {
    formError.value = null;
    success.value = false;

    try {
        const { data } = await api.update(props.invoice.id, {
            net_amount: formValues.net_amount,
            vat_amount: formValues.vat_amount,
            due_date: formValues.due_date,
        });

        success.value = true;
        emit('updated', data);
    } catch (error) {
        const err = error as {
            statusCode?: number;
            data?: { message?: string; errors?: Record<string, string[]> };
        };

        if (err.statusCode === 422 && err.data?.errors) {
            setErrors(
                Object.fromEntries(
                    Object.entries(err.data.errors).map(([field, messages]) => [
                        field,
                        messages[0],
                    ]),
                ),
            );
        } else if (err.statusCode === 409) {
            formError.value =
                err.data?.message ?? 'This invoice can no longer be edited.';
        } else {
            formError.value = 'Something went wrong while saving. Please try again.';
        }
    }
});
</script>

<template>
    <form class="space-y-5" @submit="onSubmit">
        <div
            v-if="!isEditable"
            class="rounded-md bg-gray-100 px-4 py-3 text-sm text-gray-600"
        >
            This invoice is <strong>{{ invoice.status }}</strong> and can no longer be edited.
            Only pending invoices are editable.
        </div>

        <Transition name="fade">
            <div
                v-if="formError"
                class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ formError }}
            </div>
        </Transition>

        <Transition name="fade">
            <div
                v-if="success"
                class="flex items-center gap-2 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.5 7.6a1 1 0 0 1-1.42.004l-3.5-3.5a1 1 0 1 1 1.414-1.414l2.79 2.79 6.796-6.888a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                </svg>
                Invoice updated successfully.
            </div>
        </Transition>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="net_amount" class="block text-sm font-medium text-gray-700">
                    Net amount
                </label>
                <input
                    id="net_amount"
                    v-model="netAmount"
                    v-bind="netAmountAttrs"
                    type="number"
                    step="0.01"
                    min="0"
                    :disabled="!isEditable"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none disabled:bg-gray-100 disabled:text-gray-400"
                />
                <p v-if="errors.net_amount" class="mt-1 text-xs text-red-600">
                    {{ errors.net_amount }}
                </p>
            </div>

            <div>
                <label for="vat_amount" class="block text-sm font-medium text-gray-700">
                    VAT amount
                </label>
                <input
                    id="vat_amount"
                    v-model="vatAmount"
                    v-bind="vatAmountAttrs"
                    type="number"
                    step="0.01"
                    min="0"
                    :disabled="!isEditable"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none disabled:bg-gray-100 disabled:text-gray-400"
                />
                <p v-if="errors.vat_amount" class="mt-1 text-xs text-red-600">
                    {{ errors.vat_amount }}
                </p>
            </div>

            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700">
                    Due date
                </label>
                <input
                    id="due_date"
                    v-model="dueDate"
                    v-bind="dueDateAttrs"
                    type="date"
                    :disabled="!isEditable"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none disabled:bg-gray-100 disabled:text-gray-400"
                />
                <p v-if="errors.due_date" class="mt-1 text-xs text-red-600">
                    {{ errors.due_date }}
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Gross amount (auto)
                </label>
                <p class="mt-1 block w-full rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700">
                    {{ formatMoney(grossAmount, invoice.currency) }}
                </p>
            </div>
        </div>

        <div v-if="isEditable" class="flex justify-end gap-3">
            <button
                type="button"
                :disabled="isSubmitting || isApproving"
                class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-500 disabled:cursor-not-allowed disabled:opacity-60"
                @click="emit('approve')"
            >
                <Spinner v-if="isApproving" class="h-4 w-4" />
                {{ isApproving ? 'Approving…' : 'Approve' }}
            </button>
            <button
                type="submit"
                :disabled="isSubmitting || isApproving"
                class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <Spinner v-if="isSubmitting" class="h-4 w-4" />
                {{ isSubmitting ? 'Saving…' : 'Save changes' }}
            </button>
        </div>
    </form>
</template>
