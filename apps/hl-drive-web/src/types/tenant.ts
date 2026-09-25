export interface Tenant {
    id: number | string;
    name: string;
    slug?: string;
    status?: 'active' | 'suspended' | 'deleted' | string;
    created_at?: string;
    updated_at?: string;
}
