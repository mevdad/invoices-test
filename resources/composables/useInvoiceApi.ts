import type {
    Invoice,
    InvoiceCreatePayload,
    InvoiceUpdatePayload,
    PaginatedResponse,
    ResourceResponse,
} from '~/types/invoice';

/**
 * Thin wrapper around the Laravel invoice API. Resolves the base URL per
 * environment: during SSR the server talks to Laravel directly (no CORS),
 * while the browser uses the public origin (subject to CORS).
 */
export function useInvoiceApi() {
    const config = useRuntimeConfig();
    const baseURL = import.meta.server ? config.apiBase : config.public.apiBase;

    function list(page = 1): Promise<PaginatedResponse<Invoice>> {
        return $fetch<PaginatedResponse<Invoice>>('/invoices', {
            baseURL,
            query: { page },
        });
    }

    function get(id: string): Promise<ResourceResponse<Invoice>> {
        return $fetch<ResourceResponse<Invoice>>(`/invoices/${id}`, { baseURL });
    }

    function create(
        payload: InvoiceCreatePayload,
    ): Promise<ResourceResponse<Invoice>> {
        return $fetch<ResourceResponse<Invoice>>('/invoices', {
            baseURL,
            method: 'POST',
            body: payload,
        });
    }

    function update(
        id: string,
        payload: InvoiceUpdatePayload,
    ): Promise<ResourceResponse<Invoice>> {
        return $fetch<ResourceResponse<Invoice>>(`/invoices/${id}`, {
            baseURL,
            method: 'PUT',
            body: payload,
        });
    }

    function approve(id: string): Promise<ResourceResponse<Invoice>> {
        return $fetch<ResourceResponse<Invoice>>(`/invoices/${id}/approve`, {
            baseURL,
            method: 'POST',
        });
    }

    return { list, get, create, update, approve };
}
