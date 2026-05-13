import { computed } from 'vue';
import { useAuth } from './useAuth';

export function usePermissions() {
    const auth = useAuth();

    // Client permissions
    const canViewClients = computed(() => {
        return auth.hasPermission('client.view');
    });

    const canViewAnyClients = computed(() => {
        return auth.hasPermission('client.view_any');
    });

    const canCreateClients = computed(() => {
        return auth.hasPermission('client.create');
    });

    const canCreateTimers = computed(() => {
        return auth.hasPermission('timer.create');
    });

    const canUpdateClients = computed(() => {
        return auth.hasPermission('client.update');
    });

    const canDeleteClients = computed(() => {
        return auth.hasPermission('client.delete');
    });

    const canManageClients = computed(() => {
        return auth.hasAnyPermission(['client.create', 'client.update', 'client.delete']);
    });

    // Wallet permissions
    const canViewWallets = computed(() => {
        return auth.hasPermission('wallet.view');
    });

    const canViewAnyWallets = computed(() => {
        return auth.hasPermission('wallet.view_any');
    });

    const canCreateWallets = computed(() => {
        return auth.hasPermission('wallet.create');
    });

    const canUpdateWallets = computed(() => {
        return auth.hasPermission('wallet.update');
    });

    const canDeleteWallets = computed(() => {
        return auth.hasPermission('wallet.delete');
    });

    const canManageWallets = computed(() => {
        return auth.hasAnyPermission(['wallet.create', 'wallet.update', 'wallet.delete']);
    });

    // Ledger permissions
    const canViewLedger = computed(() => {
        return auth.hasPermission('ledger.view');
    });

    const canViewAnyLedger = computed(() => {
        return auth.hasPermission('ledger.view_any');
    });

    const isCustomer = computed(() => {
        return auth.hasRole('customer') ? true : false;
    });

    const canBuyCredits = computed(() => {
        if (auth.hasRole('customer')) {
            return true;
        }

        return false;
    });

    // Credit Purchase permissions
    const canViewPaymentHistory = computed(() => {
        return auth.hasPermission('credit_purchase.view');
    });

    const canApprovePayments = computed(() => {
        return auth.hasPermission('credit_purchase.approve');
    });

    const canAddCredits = computed(() => {
        return auth.hasPermission('ledger.credit');
    });

    const canAddDebits = computed(() => {
        return auth.hasPermission('ledger.debit');
    });

    const canAddAdjustments = computed(() => {
        return auth.hasPermission('ledger.adjust');
    });

    const canAddEntry = computed(() => {
        return auth.hasAnyPermission(['ledger.credit', 'ledger.debit', 'ledger.adjust']);
    });

    // Tag permissions
    const canViewTags = computed(() => {
        return auth.hasPermission('tag.view');
    });

    const canViewAnyTags = computed(() => {
        return auth.hasPermission('tag.view_any');
    });

    const canCreateTags = computed(() => {
        return auth.hasPermission('tag.create');
    });

    const canUpdateTags = computed(() => {
        return auth.hasPermission('tag.update');
    });

    const canDeleteTags = computed(() => {
        return auth.hasPermission('tag.delete');
    });

    const canManageTags = computed(() => {
        return auth.hasAnyPermission(['tag.create', 'tag.update', 'tag.delete']);
    });

    // Report permissions
    const canViewReports = computed(() => {
        return auth.hasPermission('report.view');
    });

    const canViewAnyReports = computed(() => {
        return auth.hasPermission('report.view_any');
    });

    // Role checks
    const isSuperAdmin = computed(() => {
        return auth.hasRole('super_admin');
    });

    const isAdmin = computed(() => {
        return auth.hasAnyRole(['super_admin', 'admin']);
    });

    const isManager = computed(() => {
        return auth.hasAnyRole(['super_admin', 'admin', 'manager']);
    });

    return {
        // Client
        canViewClients,
        canViewAnyClients,
        canCreateClients,
        canUpdateClients,
        canDeleteClients,
        canManageClients,

        // Wallet
        canViewWallets,
        canViewAnyWallets,
        canCreateWallets,
        canUpdateWallets,
        canDeleteWallets,
        canManageWallets,

        // Ledger
        canViewLedger,
        canViewAnyLedger,
        canAddCredits,
        canAddDebits,
        canAddAdjustments,
        canAddEntry,
        canCreateTimers,

        // Tags
        canViewTags,
        canViewAnyTags,
        canCreateTags,
        canUpdateTags,
        canDeleteTags,
        canManageTags,

        // Reports
        canViewReports,
        canViewAnyReports,

        // Roles
        isSuperAdmin,
        isAdmin,
        isManager,

        // Customer specific
        canBuyCredits,
        isCustomer,

        // Credit Purchase
        canViewPaymentHistory,
        canApprovePayments,

        // Generic helpers
        hasPermission: auth.hasPermission,
        hasAnyPermission: auth.hasAnyPermission,
        hasAllPermissions: auth.hasAllPermissions,
        hasRole: auth.hasRole,
        hasAnyRole: auth.hasAnyRole,
    };
}
