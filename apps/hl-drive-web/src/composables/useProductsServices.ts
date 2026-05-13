import { ref, computed } from 'vue';
import api from '@/services/api';
import type { ProductService, ProductServiceForm, PaginatedResponse, GenericResponse } from '@/types';
import { useAuth } from './useAuth';

export function useProductsServices() {
    const productsServices = ref<ProductService[]>([]);
    const productService = ref<ProductService | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        total: 0,
    });

    const { hasPermission } = useAuth();

    const canView = computed(() => hasPermission('product_service.view_any'));
    const canCreate = computed(() => hasPermission('product_service.create'));
    const canUpdate = computed(() => hasPermission('product_service.update'));
    const canDelete = computed(() => hasPermission('product_service.delete'));

    async function fetchProductsServices(
        filters: {
            search?: string;
            type?: 'product' | 'service';
            is_active?: boolean;
            page?: number;
        } = {}
    ) {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();

            if (filters.search) {
                params.append('search', filters.search);
            }

            if (filters.type) {
                params.append('type', filters.type);
            }

            if (filters.is_active !== undefined) {
                params.append('is_active', filters.is_active.toString());
            }

            if (filters.page) {
                params.append('page', filters.page.toString());
            }

            const response = await api.get<PaginatedResponse<ProductService>>(
                `/products-services?${params.toString()}`
            );

            productsServices.value = (
                'data' in response.data ? response.data?.data : response.data
            ) as ProductService[];
            pagination.value = {
                currentPage: response.current_page,
                lastPage: response.last_page,
                perPage: response.per_page,
                total: response.total,
            };

            return response.data;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to fetch products/services';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchProductService(id: number) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<GenericResponse<ProductService>>(`/products-services/${id}`);
            productService.value = 'data' in response.data ? response.data?.data : response.data;

            return response.data;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to fetch product/service';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createProductService(data: ProductServiceForm) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<GenericResponse<ProductService>>('/products-services', data);
            productService.value = response.data as ProductService;

            return response.data as ProductService;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to create product/service';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateProductService(id: number, data: Partial<ProductServiceForm>) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<GenericResponse<ProductService>>(`/products-services/${id}`, data);
            productService.value = 'data' in response.data ? response.data?.data : response.data;

            return productService.value;
        } finally {
            loading.value = false;
        }
    }

    async function deleteProductService(id: number) {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/products-services/${id}`);

            const index = productsServices.value.findIndex((ps) => ps.id === id);

            if (index !== -1) {
                productsServices.value.splice(index, 1);
            }

            if (productService.value?.id === id) {
                productService.value = null;
            }
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to delete product/service';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        productsServices,
        productService,
        loading,
        error,
        pagination,
        canView,
        canCreate,
        canUpdate,
        canDelete,
        fetchProductsServices,
        fetchProductService,
        createProductService,
        updateProductService,
        deleteProductService,
    };
}
