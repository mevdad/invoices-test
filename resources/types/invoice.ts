export type InvoiceStatus = 'pending' | 'approved' | 'rejected';

export interface Invoice {
    id: string;
    number: string;
    supplier_name: string;
    supplier_tax_id: string;
    net_amount: string;
    vat_amount: string;
    gross_amount: string;
    currency: string;
    status: InvoiceStatus;
    issue_date: string;
    due_date: string;
    created_at: string | null;
    updated_at: string | null;
}

export interface InvoiceUpdatePayload {
    net_amount: number;
    vat_amount: number;
    due_date: string;
}

export interface InvoiceCreatePayload {
    number: string;
    supplier_name: string;
    supplier_tax_id: string;
    net_amount: number;
    vat_amount: number;
    currency: string;
    issue_date: string;
    due_date: string;
}

export interface ResourceResponse<T> {
    data: T;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
}
