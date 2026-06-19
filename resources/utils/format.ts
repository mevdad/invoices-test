/**
 * Deterministic formatters — identical output on server and client to avoid
 * SSR hydration mismatches (no locale/timezone-dependent Intl output).
 */

export function formatMoney(amount: string | number, currency = 'UAH'): string {
    const value = typeof amount === 'string' ? Number(amount) : amount;
    const safe = Number.isFinite(value) ? value : 0;
    const grouped = safe.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

    return `${grouped} ${currency}`;
}

const MONTHS = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
];

export function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    const date = new Date(iso);
    const day = String(date.getUTCDate()).padStart(2, '0');

    return `${day} ${MONTHS[date.getUTCMonth()]} ${date.getUTCFullYear()}`;
}

export function formatDateTime(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    const date = new Date(iso);
    const hours = String(date.getUTCHours()).padStart(2, '0');
    const minutes = String(date.getUTCMinutes()).padStart(2, '0');

    return `${formatDate(iso)}, ${hours}:${minutes} UTC`;
}
