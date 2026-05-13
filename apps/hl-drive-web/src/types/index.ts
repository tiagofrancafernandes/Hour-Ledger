// export type {
//     RequestInit,
// } from 'undici-types'

export interface Client {
    id: number;
    name: string;
    notes: string | null;
    customer_since?: string | null;
    total_balance?: string;
    wallets?: WalletWithBalance[];
    users?: User[];
    created_at: string;
    updated_at: string;
}

export interface Wallet {
    id: number;
    client_id: number;
    name: string;
    description: string | null;
    internal_note?: string | null;
    hourly_rate_reference: string | null;
    currency_code: string | null;
    client?: Client;
    credit_purchase_allowed: boolean;
    created_at: string;
    updated_at: string;
}

export interface WalletWithBalance extends Wallet {
    balance: string;
}

export interface Tag {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface LedgerEntry {
    id: number;
    wallet_id: number;
    hours: string;
    title: string | null;
    description: string | null;
    reference_date: string | null;
    reference_date_timezone?: string | null;
    wallet?: Wallet;
    tags?: Tag[];
    created_at: string;
    updated_at: string;
}

/*
const response = await api.post<GenericResponse<ProductService>>('/endpoint', data);
notArrayValue = response.data as ProductService;
// ------
const response = await api.post<GenericResponse<ProductService[]>>('/endpoint', data);
anArrayValue = response.data as ProductService[];
*/
export interface GenericResponse<T> {
    data:
        | T
        | {
              data: T;
          };
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface ReportSummary {
    total_credits: string;
    total_debits: string;
    net_balance: string;
    entry_count: number;
}

export interface ReportFilters {
    client_id?: number | null;
    wallet_id?: number | null;
    date_from?: string;
    date_to?: string;
    tags?: number[];
    type?: 'credit' | 'debit' | null;
    group_by?: 'wallet' | 'client' | null;
    per_page?: number;
}

export interface LedgerEntryForm {
    wallet_id: number;
    type: 'credit' | 'debit' | 'adjustment';
    hours: number;
    title?: string;
    description?: string;
    reference_date?: string;
    reference_date_timezone?: string | null;
    tags?: number[];
}

export interface Role {
    id: string | number;
    name: string;
    notes: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    customer_id?: number | null;
    client_role?: 'admin' | 'member' | null;
    client?: Client | null;
    role?: string;
    timezone?: string | null | undefined;
    roles?: Role[];
    created_at?: string;
    updated_at?: string;
}

export interface AuthState {
    user: User | null;
    role: string | null;
    permissions: string[];
    token: string | null;
    isAuthenticated: boolean;
}

export interface LoginCredentials {
    email: string;
    password: string;
    remember?: string | boolean | undefined;
}

export interface LoginResponse {
    user: User;
    role: string | null;
    permissions: string[];
    token: string;
}

export interface ValidateTokenResponse {
    valid: boolean;
    user: User;
    role: string | null;
    permissions: string[];
}

export type TimerStatus = 'running' | 'paused' | 'stopped' | 'confirmed' | 'cancelled';

export interface TimerCycle {
    id: number;
    timer_id: number;
    started_at: string;
    ended_at: string | null;
    duration_seconds: number;
    created_at: string;
    updated_at: string;
}

export interface Timer {
    id: number;
    user_id: number;
    wallet_id: number;
    title: string | null;
    description: string | null;
    status: TimerStatus;
    confirmed_at: string | null;
    ledger_entry_id: number | null;
    total_seconds: number;
    formatted_duration: string;
    total_hours: number;
    cycles: TimerCycle[];
    user?: User;
    wallet?: Wallet;
    tags?: Tag[];
    ledger_entry?: LedgerEntry;
    created_at: string;
    updated_at: string;
}

export interface TimerForm {
    wallet_id: number;
    title?: string;
    description?: string;
    tags?: number[];
}

export interface TimerCycleForm {
    id?: number;
    started_at: string;
    ended_at?: string | null;
}

export type ImportPlanStatus = 'pending' | 'validated' | 'confirmed' | 'cancelled';

export interface ImportPlanSummary {
    total_rows: number;
    valid_rows: number;
    invalid_rows: number;
    total_hours: number;
}

export interface ImportPlanRow {
    id: number;
    import_plan_id: number;
    row_number: number;
    reference_date: string;
    start_time: string | null;
    end_time: string | null;
    hours: number | null;
    title: string;
    description: string | null;
    input_type: 'debit' | 'credit' | 'adjustment';
    tags: string[];
    validation_errors: string[];
    is_valid: boolean;
    ledger_entry_id: number | null;
    created_at: string;
    updated_at: string;
}

export interface ImportPlan {
    id: number;
    user_id: number;
    wallet_id: number;
    original_filename: string;
    file_path: string;
    status: ImportPlanStatus;
    summary: ImportPlanSummary | null;
    validation_errors: string[] | null;
    confirmed_at: string | null;
    user?: User;
    wallet?: Wallet;
    rows?: ImportPlanRow[];
    created_at: string;
    updated_at: string;
}

export interface ImportPlanForm {
    wallet_id: number;
    file: File;
}

export interface ImportRowForm {
    reference_date: string;
    start_time?: string | null;
    end_time?: string | null;
    hours?: number | null;
    title: string;
    description?: string | null;
    input_type?: 'debit' | 'credit' | 'adjustment';
    tags?: string[];
}

export interface PaymentMethod {
    key: string;
    name: string;
    label: string;
    is_active: boolean;
    is_offline: boolean;
    currency: string | null;
    value: unknown;
    expires_time: string | number | null;
    accepts_discount: boolean;
    allowed_users: string[];
}

export interface CreditPurchasePayment {
    id: number;
    credit_purchase_id: number;
    payment_method: PaymentMethod | null;
    payment_status: 'pending' | 'approved' | 'rejected' | 'completed';
    pix_receipt_path?: string | null;
    receipt_approved_by?: number | null;
    receipt_approved_at?: string | null;
    notes?: string | null;
    expires_at?: string | null;
    approvedBy?: User;
    created_at: string;
    updated_at: string;
}

export interface CreditPurchase {
    id: number;
    wallet_id: number;
    customer_id: number;
    total_hours: string;
    total_price: string;
    currency_code: string;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    wallet?: Wallet;
    customer?: User;
    payments?: CreditPurchasePayment[];
    created_at: string;
    updated_at: string;
}

export interface CreditPurchaseForm {
    wallet_id: number;
    total_hours: number;
    total_price: number;
}

export interface ClientUserForm {
    client_id: number;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export interface CreateUserForm {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
    customer_id?: number | null;
    client_role?: 'admin' | 'member' | null | undefined;
}

export interface UpdateClientUserForm {
    name?: string;
    email?: string;
    password?: string;
    password_confirmation?: string;
    client_id?: number | null;
}

export type TypeaheadOption = {
    value: unknown;
    label: string;
};

export interface ProductService {
    id: number;
    name: string;
    description: string | null;
    type: 'product' | 'service';
    unit: string;
    default_price: string;
    default_quantity: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface InvoiceItem {
    id?: number;
    invoice_id?: number;
    line_number: number;
    description: string;
    unit: string;
    quantity: string;
    unit_price: string;
    line_total: string;
    product_service_id: number | null;
    product_service?: ProductService;
    created_at?: string;
    updated_at?: string;
}

export interface Invoice {
    id: number;
    client_id: number;
    invoice_number: number;
    client_invoice_number: number;
    issue_date: string;
    due_date: string | null;
    currency: string;
    subtotal: string;
    tax_amount: string;
    tax_percentage: string;
    discount_amount: string;
    total: string;
    status: 'draft' | 'sent' | 'paid' | 'cancelled';
    hidden: boolean;
    notes: string | null;
    issued_by: Record<string, unknown> | null;
    bill_to: Record<string, unknown> | null;
    client?: Client;
    items?: InvoiceItem[];
    created_at: string;
    updated_at: string;
}

export interface InvoiceForm {
    client_id: number;
    issue_date: string | null | undefined;
    due_date?: string | null;
    currency: string;
    tax_percentage?: number;
    discount_amount?: number;
    status?: 'draft' | 'sent' | 'paid' | 'cancelled';
    hidden?: boolean;
    notes?: string | null;
    issued_by?: Record<string, unknown> | null;
    bill_to?: Record<string, unknown> | null;
    items: InvoiceItemForm[];
}

export interface InvoiceItemForm {
    id?: number;
    description: string;
    unit?: string;
    quantity: number;
    unit_price: number;
    product_service_id?: number | null;
}

export interface ProductServiceForm {
    name: string;
    description?: string | null;
    type: 'product' | 'service';
    unit?: string;
    default_price: number;
    default_quantity?: number;
    is_active?: boolean;
}

export type TimezoneConfig = {
    offset: number;
    label: string;
    timezone_id: string;
    country: string;
};
