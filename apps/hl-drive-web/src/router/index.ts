import { createRouter, createWebHistory } from 'vue-router';
import type { RouteLocationNormalized, NavigationGuardNext } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const APP_TITLE = 'Hours Ledger';

declare module 'vue-router' {
    interface RouteMeta {
        title?: string;
        requiresAuth?: boolean;
        requiresGuest?: boolean;
        permissions?: string[];
    }
}

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/LoginView.vue'),
            meta: {
                title: 'Login',
                requiresGuest: true,
            },
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/views/RegisterView.vue'),
            meta: {
                title: 'Register',
                requiresGuest: true,
            },
        },
        {
            path: '/password-recovery',
            name: 'password-recovery',
            component: () => import('@/views/PasswordRecoveryView.vue'),
            meta: {
                title: 'Reset Password',
                requiresGuest: true,
            },
        },
        {
            path: '/',
            name: 'home',
            redirect: (to) => {
                const auth = useAuth();

                if (auth.isCustomer.value) {
                    return '/dashboard';
                }

                return '/clients';
            },
        },
        {
            path: '/dashboard',
            name: 'customer-dashboard',
            component: () => import('@/views/CustomerDashboardView.vue'),
            meta: {
                title: 'Dashboard',
                requiresAuth: true,
            },
        },
        {
            path: '/clients',
            name: 'clients',
            component: () => import('@/views/ClientsView.vue'),
            meta: {
                title: 'Clients',
                requiresAuth: true,
            },
        },
        {
            path: '/clients/:id',
            name: 'client-detail',
            component: () => import('@/views/ClientDetailView.vue'),
            meta: {
                title: 'Client Details',
                requiresAuth: true,
            },
        },
        {
            path: '/wallets/:id',
            name: 'wallet-detail',
            component: () => import('@/views/WalletDetailView.vue'),
            meta: {
                title: 'Wallet Details',
                requiresAuth: true,
            },
        },
        {
            path: '/reports',
            name: 'reports',
            component: () => import('@/views/ReportsView.vue'),
            meta: {
                title: 'Reports',
                requiresAuth: true,
            },
        },
        {
            path: '/tags',
            name: 'tags',
            component: () => import('@/views/TagsView.vue'),
            meta: {
                title: 'Tags',
                requiresAuth: true,
            },
        },
        {
            path: '/timers',
            name: 'timers',
            component: () => import('@/views/TimersView.vue'),
            meta: {
                title: 'Timers',
                requiresAuth: true,
                permissions: ['timer.view'],
            },
        },
        {
            path: '/imports',
            name: 'imports',
            component: () => import('@/views/ImportPlansListView.vue'),
            meta: {
                title: 'Imports',
                requiresAuth: true,
                permissions: ['import.view'],
            },
        },
        {
            path: '/imports/upload',
            name: 'imports-upload',
            component: () => import('@/views/ImportUploadView.vue'),
            meta: {
                title: 'New Import',
                requiresAuth: true,
                permissions: ['import.create'],
            },
        },
        {
            path: '/imports/:id/review',
            name: 'imports-review',
            component: () => import('@/views/ImportReviewView.vue'),
            meta: {
                title: 'Review Import',
                requiresAuth: true,
                permissions: ['import.view'],
            },
        },
        {
            path: '/profile',
            name: 'profile',
            component: () => import('@/views/ProfileView.vue'),
            meta: {
                title: 'Profile',
                requiresAuth: true,
            },
        },
        {
            path: '/payments/history',
            name: 'payment-history',
            component: () => import('@/views/PaymentHistoryView.vue'),
            meta: {
                title: 'Payment History',
                requiresAuth: true,
                permissions: ['credit_purchase.view'],
            },
        },
        {
            path: '/payments/approvals',
            name: 'payment-approvals',
            component: () => import('@/views/AdminPaymentApprovalView.vue'),
            meta: {
                title: 'Payment Approvals',
                requiresAuth: true,
                permissions: ['credit_purchase.approve'],
            },
        },
        {
            path: '/admin/users',
            name: 'admin-users',
            component: () => import('@/views/AdminUsersView.vue'),
            meta: {
                title: 'User Management',
                requiresAuth: true,
                permissions: ['user.view_any'],
            },
        },
        {
            path: '/invoices',
            name: 'invoices',
            component: () => import('@/views/InvoicesView.vue'),
            meta: {
                title: 'Invoices',
                requiresAuth: true,
                permissions: ['invoice.view'],
            },
        },
        {
            path: '/invoices/new',
            name: 'invoice-create',
            component: () => import('@/views/InvoiceFormView.vue'),
            meta: {
                title: 'New Invoice',
                requiresAuth: true,
                permissions: ['invoice.create'],
            },
        },
        {
            path: '/invoices/:id',
            name: 'invoice-detail',
            component: () => import('@/views/InvoiceDetailView.vue'),
            meta: {
                title: 'Invoice Details',
                requiresAuth: true,
                permissions: ['invoice.view'],
            },
        },
        {
            path: '/invoices/:id/edit',
            name: 'invoice-edit',
            component: () => import('@/views/InvoiceFormView.vue'),
            meta: {
                title: 'Edit Invoice',
                requiresAuth: true,
                permissions: ['invoice.update'],
            },
        },
        {
            path: '/products-services',
            name: 'products-services',
            component: () => import('@/views/ProductsServicesView.vue'),
            meta: {
                title: 'Products & Services',
                requiresAuth: true,
                permissions: ['product_service.view_any'],
            },
        },
    ],
});

router.beforeEach(async (to: RouteLocationNormalized, _from: RouteLocationNormalized, next: NavigationGuardNext) => {
    const auth = useAuth();

    if (!auth.initialized.value) {
        await auth.initialize();
    }

    const isAuthenticated = auth.isAuthenticated.value;

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({
            name: 'login',
            query: { redirect: to.fullPath },
        });

        return;
    }

    if (to.meta.requiresGuest && isAuthenticated) {
        next({ name: 'home' });

        return;
    }

    // Redirect customers away from admin routes
    if (isAuthenticated && auth.isCustomer.value) {
        const adminRoutes = [
            'clients',
            'tags',
            'timers',
            'imports',
            'imports-upload',
            'imports-review',
            'invoice-create',
            'invoice-edit',
            'products-services',
        ];

        if (adminRoutes.includes(to.name as string)) {
            next({ name: 'customer-dashboard' });

            return;
        }

        // Block customers from viewing other clients' details
        if (to.name === 'client-detail') {
            const clientId = Number(to.params.id);
            const customerId = auth.getCustomerId();

            if (clientId !== customerId) {
                next({ name: 'customer-dashboard' });

                return;
            }
        }
    }

    if (to.meta.permissions && to.meta.permissions.length > 0) {
        const hasPermission = auth.hasAnyPermission(to.meta.permissions);

        if (!hasPermission) {
            next({ name: 'home' });

            return;
        }
    }

    next();
});

router.afterEach((to: RouteLocationNormalized) => {
    const pageTitle = to.meta.title;

    if (pageTitle) {
        document.title = `${pageTitle} - ${APP_TITLE}`;
    } else {
        document.title = APP_TITLE;
    }
});

export default router;
