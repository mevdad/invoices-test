<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import * as z from 'zod';

const api = useInvoiceApi();

useSeoMeta({ title: 'New invoice' });

const validationSchema = toTypedSchema(
    z
        .object({
            number: z.string().min(1, 'Number is required'),
            supplier_name: z.string().min(1, 'Supplier name is required'),
            supplier_tax_id: z.string().min(1, 'Supplier tax ID is required'),
            net_amount: z.coerce
                .number({ message: 'Net amount is required' })
                .gt(0, 'Net amount must be greater than 0'),
            vat_amount: z.coerce
                .number({ message: 'VAT amount is required' })
                .gte(0, 'VAT amount cannot be negative'),
            currency: z
                .string()
                .length(3, 'Currency must be a 3-letter code')
                .transform((value) => value.toUpperCase()),
            issue_date: z.string().min(1, 'Issue date is required'),
            due_date: z.string().min(1, 'Due date is required'),
        })
        .refine((data) => data.due_date >= data.issue_date, {
            path: ['due_date'],
            message: 'Due date must be on or after the issue date',
        }),
);

const { handleSubmit, defineField, setErrors, values, errors, isSubmitting } = useForm({
    validationSchema,
    initialValues: {
        number: '',
        supplier_name: '',
        supplier_tax_id: '',
        net_amount: undefined,
        vat_amount: undefined,
        currency: 'UAH',
        issue_date: '',
        due_date: '',
    },
});

const [number, numberAttrs] = defineField('number');
const [supplierName, supplierNameAttrs] = defineField('supplier_name');
const [supplierTaxId, supplierTaxIdAttrs] = defineField('supplier_tax_id');
const [netAmount, netAmountAttrs] = defineField('net_amount');
const [vatAmount, vatAmountAttrs] = defineField('vat_amount');
const [currency, currencyAttrs] = defineField('currency');
const [issueDate, issueDateAttrs] = defineField('issue_date');
const [dueDate, dueDateAttrs] = defineField('due_date');

const grossAmount = computed(
    () => Number(values.net_amount || 0) + Number(values.vat_amount || 0),
);

const formError = ref<string | null>(null);
const success = ref(false);

// Keep the button busy from submit until the redirect completes.
const isBusy = computed(() => isSubmitting.value || success.value);

// Pending "show success, then redirect" timer — cleared if the component is
// unmounted first, so navigateTo never fires after the user has left.
let redirectTimer: ReturnType<typeof setTimeout> | undefined;

onBeforeUnmount(() => clearTimeout(redirectTimer));

const onSubmit = handleSubmit(async (formValues) => {
    formError.value = null;

    try {
        const { data } = await api.create({
            number: formValues.number,
            supplier_name: formValues.supplier_name,
            supplier_tax_id: formValues.supplier_tax_id,
            net_amount: formValues.net_amount,
            vat_amount: formValues.vat_amount,
            currency: formValues.currency,
            issue_date: formValues.issue_date,
            due_date: formValues.due_date,
        });

        // Show the success message briefly, then go to the new invoice.
        success.value = true;
        redirectTimer = setTimeout(() => navigateTo(`/invoices/${data.id}`), 900);
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
        } else {
            formError.value = 'Something went wrong while saving. Please try again.';
        }
    }
});
</script>

<template>
    <section>
        <NuxtLink to="/invoices" class="text-sm text-gray-500 hover:text-gray-700">
            ← Back to invoices
        </NuxtLink>

        <h1 class="mt-4 text-xl font-semibold">New invoice</h1>

        <form class="mt-6 space-y-5 rounded-lg border border-gray-200 bg-white p-6" @submit="onSubmit">
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
                    Invoice created successfully. Redirecting…
                </div>
            </Transition>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="number" class="block text-sm font-medium text-gray-700">Number</label>
                    <input
                        id="number"
                        v-model="number"
                        v-bind="numberAttrs"
                        type="text"
                        placeholder="INV-0001"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.number" class="mt-1 text-xs text-red-600">{{ errors.number }}</p>
                </div>

                <div>
                    <label for="supplier_name" class="block text-sm font-medium text-gray-700">Supplier name</label>
                    <input
                        id="supplier_name"
                        v-model="supplierName"
                        v-bind="supplierNameAttrs"
                        type="text"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.supplier_name" class="mt-1 text-xs text-red-600">{{ errors.supplier_name }}</p>
                </div>

                <div>
                    <label for="supplier_tax_id" class="block text-sm font-medium text-gray-700">Supplier tax ID</label>
                    <input
                        id="supplier_tax_id"
                        v-model="supplierTaxId"
                        v-bind="supplierTaxIdAttrs"
                        type="text"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.supplier_tax_id" class="mt-1 text-xs text-red-600">{{ errors.supplier_tax_id }}</p>
                </div>

                <div>
                    <label for="net_amount" class="block text-sm font-medium text-gray-700">Net amount</label>
                    <input
                        id="net_amount"
                        v-model="netAmount"
                        v-bind="netAmountAttrs"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.net_amount" class="mt-1 text-xs text-red-600">{{ errors.net_amount }}</p>
                </div>

                <div>
                    <label for="vat_amount" class="block text-sm font-medium text-gray-700">VAT amount</label>
                    <input
                        id="vat_amount"
                        v-model="vatAmount"
                        v-bind="vatAmountAttrs"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.vat_amount" class="mt-1 text-xs text-red-600">{{ errors.vat_amount }}</p>
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
                    <input
                        id="currency"
                        v-model="currency"
                        v-bind="currencyAttrs"
                        type="text"
                        maxlength="3"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm uppercase shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.currency" class="mt-1 text-xs text-red-600">{{ errors.currency }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Gross amount (auto)</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700">
                        {{ formatMoney(grossAmount, values.currency || 'UAH') }}
                    </p>
                </div>

                <div>
                    <label for="issue_date" class="block text-sm font-medium text-gray-700">Issue date</label>
                    <input
                        id="issue_date"
                        v-model="issueDate"
                        v-bind="issueDateAttrs"
                        type="date"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.issue_date" class="mt-1 text-xs text-red-600">{{ errors.issue_date }}</p>
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due date</label>
                    <input
                        id="due_date"
                        v-model="dueDate"
                        v-bind="dueDateAttrs"
                        type="date"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none"
                    />
                    <p v-if="errors.due_date" class="mt-1 text-xs text-red-600">{{ errors.due_date }}</p>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <NuxtLink
                    to="/invoices"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Cancel
                </NuxtLink>
                <button
                    type="submit"
                    :disabled="isBusy"
                    class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <Spinner v-if="isBusy" class="h-4 w-4" />
                    {{ isBusy ? 'Creating…' : 'Create invoice' }}
                </button>
            </div>
        </form>
    </section>
</template>
