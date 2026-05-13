<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useClients } from '@/composables/useClients';
import { useWallets } from '@/composables/useWallets';
import { useClientUsers } from '@/composables/useClientUsers';
import { useUsers } from '@/composables/useUsers';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import { useTimerStore } from '@/stores/timer';
import type { User, WalletWithBalance, ClientUserForm, UpdateClientUserForm } from '@/types';

const route = useRoute();
const router = useRouter();
const clientId = Number(route.params.id);
const timerStore = useTimerStore();

const { client, loading: clientLoading, error: clientError, fetchClient } = useClients();
const { createWallet, updateWallet } = useWallets();
const startingTimerId = ref<number | null>(null);
const {
    users: clientUsers,
    loading: usersLoading,
    error: clientUsersError,
    fetchClientUsers,
    createClientUser,
    attachUser,
    setClientAdmin,
    updateClientUser,
    deleteClientUser,
} = useClientUsers();
const { searchUsers } = useUsers();
const { canManageWallets, canManageClients, hasPermission } = usePermissions();
const toast = useToast();

const showCreateWalletModal = ref(false);
const showEditWalletModal = ref(false);
const editingWallet = ref<WalletWithBalance | null>(null);
const newWalletName = ref('');
const newWalletDescription = ref('');
const newWalletHourlyRate = ref<number | undefined>(undefined);
const newWalletCurrencyCode = ref('USD');
const newWalletInternalNote = ref('');
const newWalletCreditPurchaseAllowed = ref(false);

const canUpdateRules = computed(() => hasPermission('wallet.update_rules'));
const canViewInternalNote = computed(() => {
    return hasPermission('wallet.view_internal_note');
});

const currencyCodes = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'INR', 'MXN', 'BRL'];

const newWalletValidationErrors = ref<Record<string, string>>({});

function setNewWalletValidationErrors(value: any) {
    newWalletValidationErrors.value = value;
}

const isNewWalletFormValid = computed(() => {
    const errors: Record<string, string> = {};

    if (!newWalletName.value.trim()) {
        errors.name = 'Wallet name is required';
    }

    if (newWalletCreditPurchaseAllowed.value) {
        if (!newWalletHourlyRate.value || newWalletHourlyRate.value <= 0) {
            errors.hourly_rate_reference = 'Hourly rate is required when credit purchase is enabled';
        }

        if (!newWalletCurrencyCode.value) {
            errors.currency_code = 'Currency is required when credit purchase is enabled';
        }
    }

    setNewWalletValidationErrors(errors);

    return Object.keys(errors).length === 0;
});

const showCreateUserModal = ref(false);
const showEditUserModal = ref(false);
const editingUser = ref<(User & { password?: string; password_confirmation?: string }) | null>(null);
const userForm = ref<ClientUserForm>({
    client_id: clientId,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

onMounted(() => {
    fetchClient(clientId);
    fetchClientUsers(clientId);
});

async function handleCreateWallet() {
    if (!isNewWalletFormValid.value) {
        const firstError = Object.values(newWalletValidationErrors.value)[0];

        if (firstError) {
            toast.error(firstError);
        }

        return;
    }

    try {
        const payload = {
            client_id: clientId,
            name: newWalletName.value,
            description: newWalletDescription.value || undefined,
            hourly_rate_reference: newWalletHourlyRate.value,
            currency_code: newWalletCurrencyCode.value || undefined,
            ...(canViewInternalNote.value ? { internal_note: newWalletInternalNote.value || undefined } : {}),
            ...(canUpdateRules.value ? { credit_purchase_allowed: newWalletCreditPurchaseAllowed.value } : {}),
        };

        await createWallet(payload);

        showCreateWalletModal.value = false;
        newWalletName.value = '';
        newWalletDescription.value = '';
        newWalletHourlyRate.value = undefined;
        newWalletCurrencyCode.value = 'USD';
        newWalletInternalNote.value = '';
        newWalletCreditPurchaseAllowed.value = false;
        fetchClient(clientId);
    } catch {
        // Error handled in composable
    }
}

function handleEditWallet(wallet: WalletWithBalance, event: Event) {
    event.stopPropagation();
    editingWallet.value = { ...wallet };
    showEditWalletModal.value = true;
}

async function handleUpdateWallet() {
    if (!editingWallet.value || !editingWallet.value.name.trim()) {
        return;
    }

    try {
        await updateWallet(editingWallet.value.id, {
            name: editingWallet.value.name,
            description: editingWallet.value.description || undefined,
            hourly_rate_reference: editingWallet.value.hourly_rate_reference
                ? parseFloat(String(editingWallet.value.hourly_rate_reference))
                : undefined,
        });

        showEditWalletModal.value = false;
        editingWallet.value = null;
        fetchClient(clientId);
    } catch {
        // Error handled in composable
    }
}

function goToWallet(walletId: number) {
    router.push({ name: 'wallet-detail', params: { id: walletId } });
}

function formatBalance(balance: string): string {
    const num = parseFloat(balance);

    if (num > 0) {
        return `+${balance}h`;
    }

    return `${balance}h`;
}

function getBalanceColor(balance: string): string {
    const num = parseFloat(balance);

    if (num > 0) {
        return 'text-green-600';
    }

    if (num < 0) {
        return 'text-red-600';
    }

    return 'text-gray-600';
}

// ─── Timer Management ────────────────────────────────────────────────────────

async function handleStartTimer(wallet: WalletWithBalance, event: Event): Promise<void> {
    event.stopPropagation();

    startingTimerId.value = wallet.id;

    try {
        const success = await timerStore.startTimer({
            wallet_id: wallet.id,
        });

        if (success) {
            toast.success('Timer started successfully');
        } else {
            const errorMessage = timerStore.error || 'Failed to start timer';

            toast.error(errorMessage);
        }
    } catch (err: any) {
        const errorMessage = err?.response?.data?.message || err?.message || 'Failed to start timer';

        toast.error(errorMessage);
    } finally {
        startingTimerId.value = null;
    }
}

const hasClientUsers = computed(() => {
    return clientUsers.value.length > 0;
});

// ─── Attach Existing User Modal ───────────────────────────────────────────────

const showAttachModal = ref(false);
const attachSelected = ref<User | null>(null);
const attachRole = ref<'admin' | 'member'>('member');
const attachLoading = ref(false);
const attachUserCache = new Map<number, User>();

function openAttachModal(): void {
    showAttachModal.value = true;
    attachSelected.value = null;
    attachRole.value = 'member';
}

function closeAttachModal(): void {
    showAttachModal.value = false;
    attachSelected.value = null;
    attachUserCache.clear();
}

async function searchAttachableUsers({ searchTerm }: { searchTerm: string }) {
    if (!searchTerm.trim()) {
        return [];
    }

    const results = await searchUsers(searchTerm.trim(), { without_client: 1, role: 'customer' });

    if (!results || !results.length) {
        return;
    }

    results.forEach((u) => {
        attachUserCache.set(u.id, u);
    });

    return results.map((u) => ({ value: u.id, label: u.name }));
}

function onAttachUserChange({ value }: { value: unknown }): void {
    if (!value) {
        attachSelected.value = null;

        return;
    }

    const user = attachUserCache.get(value as number);

    attachSelected.value = user ?? null;
}

async function handleAttachUser(): Promise<void> {
    if (!attachSelected.value) {
        return;
    }

    const hasAdmin = clientUsers.value.some((u) => u.client_role === 'admin');
    const role = hasAdmin ? attachRole.value : 'admin';

    attachLoading.value = true;

    try {
        await attachUser(clientId, attachSelected.value.id, role);

        closeAttachModal();
        toast.success('User linked successfully');
    } catch {
        const errorMessage = clientUsersError.value || 'Failed to link user';

        toast.error(errorMessage);
    } finally {
        attachLoading.value = false;
    }
}

// ─── Set Client Admin ─────────────────────────────────────────────────────────

async function handleSetAdmin(userId: number): Promise<void> {
    try {
        await setClientAdmin(clientId, userId);

        toast.success('Admin updated successfully');
    } catch {
        const errorMessage = clientUsersError.value || 'Failed to update admin';

        toast.error(errorMessage);
    }
}

async function handleCreateUser() {
    if (!userForm.value.name || !userForm.value.email || !userForm.value.password) {
        return;
    }

    try {
        await createClientUser(clientId, userForm.value);

        showCreateUserModal.value = false;

        userForm.value = {
            client_id: clientId,
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        };

        toast.success('User created successfully');
    } catch {
        const errorMessage = clientUsersError.value || 'Failed to create user';

        toast.error(errorMessage);
    }
}

function handleEditUser(user: User) {
    editingUser.value = { ...user };
    showEditUserModal.value = true;
}

async function handleUpdateUser() {
    if (!editingUser.value) {
        return;
    }

    try {
        const updateData: UpdateClientUserForm = {
            name: editingUser.value.name,
            email: editingUser.value.email,
        };

        await updateClientUser(clientId, editingUser.value.id, updateData);

        showEditUserModal.value = false;
        editingUser.value = null;

        toast.success('User updated successfully');
    } catch {
        const errorMessage = clientUsersError.value || 'Failed to update user';

        toast.error(errorMessage);
    }
}

async function handleDeleteUser(userId: number) {
    if (!confirm('Are you sure you want to delete this user?')) {
        return;
    }

    try {
        await deleteClientUser(clientId, userId);

        toast.success('User deleted successfully');
    } catch {
        const errorMessage = clientUsersError.value || 'Failed to delete user';

        toast.error(errorMessage);
    }
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <button
            class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
            @click="router.push({ name: 'clients' })"
        >
            ← Back to Clients
        </button>

        <div v-if="clientError" class="mb-4 rounded-lg bg-red-100 p-4 text-red-700">
            {{ clientError }}
        </div>

        <div v-if="clientLoading" class="py-8 text-center">Loading...</div>

        <div v-else-if="client">
            <div class="mb-6 rounded-lg bg-white p-6 shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ client.name }}</h1>
                        <p v-if="client.notes" class="mt-1 text-gray-600">
                            {{ client.notes }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total Balance</p>
                        <p class="text-2xl font-bold" :class="getBalanceColor(client.total_balance || '0')">
                            {{ formatBalance(client.total_balance || '0') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Wallets</h2>
                <CButton v-if="canManageWallets" @click="showCreateWalletModal = true">New Wallet</CButton>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="wallet in client.wallets"
                    :key="wallet.id"
                    class="cursor-pointer rounded-lg bg-white p-4 shadow transition-shadow hover:shadow-md"
                    @click="goToWallet(wallet.id)"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ wallet.name }}</h3>
                            <p v-if="wallet.description" class="mt-1 text-sm text-gray-500">
                                {{ wallet.description }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-0.5',
                                        wallet.credit_purchase_allowed
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{
                                        wallet.credit_purchase_allowed
                                            ? 'Credit purchases allowed'
                                            : 'Credit purchases disabled'
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold" :class="getBalanceColor(wallet.balance)">
                                {{ formatBalance(wallet.balance) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div v-if="wallet.hourly_rate_reference" class="text-sm text-gray-500">
                            Rate: ${{ wallet.hourly_rate_reference }}/h
                        </div>
                        <div class="ml-auto flex items-center gap-2">
                            <CButton
                                class="inline-flex items-center gap-1 text-sm"
                                icon="mdi:play-outline"
                                :disabled="startingTimerId === wallet.id"
                                @click="handleStartTimer(wallet, $event)"
                            >
                                {{ startingTimerId === wallet.id ? 'Starting...' : 'Start Timer' }}
                            </CButton>
                            <button
                                v-if="canManageWallets"
                                class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                                @click="handleEditWallet(wallet, $event)"
                            >
                                Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!client.wallets?.length" class="py-8 text-center text-gray-500">
                No wallets yet. Create one to start tracking hours.
            </div>

            <!-- Users Section -->
            <div class="mt-8">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Users</h2>

                    <div v-if="canManageClients" class="flex gap-2">
                        <CButton preset="outlined-black" icon="mdi:account-arrow-right" @click="openAttachModal">
                            Attach Existing
                        </CButton>

                        <CButton preset="primary" icon="mdi:account-plus" @click="showCreateUserModal = true">
                            New User
                        </CButton>
                    </div>
                </div>

                <div v-if="usersLoading" class="py-8 text-center">
                    <div
                        class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-red-600 mx-auto"
                    ></div>
                </div>

                <div v-else-if="!hasClientUsers" class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                    <Icon icon="mdi:account-group-outline" class="mx-auto mb-2 text-4xl text-gray-400" />
                    <p class="text-gray-600">No users registered for this client</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="user in clientUsers"
                        :key="user.id"
                        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-700"
                                >
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-semibold text-gray-900">{{ user.name }}</h4>

                                        <span
                                            v-if="user.client_role === 'admin'"
                                            class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700"
                                        >
                                            <Icon icon="mdi:crown" class="text-xs" />
                                            Admin
                                        </span>

                                        <span
                                            v-else-if="user.client_role === 'member'"
                                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600"
                                        >
                                            Member
                                        </span>
                                    </div>

                                    <p class="mt-0.5 text-sm text-gray-500">{{ user.email }}</p>
                                </div>
                            </div>

                            <div v-if="canManageClients" class="flex shrink-0 gap-2">
                                <CButton
                                    v-if="user.client_role !== 'admin'"
                                    preset="outlined-black"
                                    icon="mdi:crown-outline"
                                    class="text-xs"
                                    @click="handleSetAdmin(user.id)"
                                >
                                    Set Admin
                                </CButton>

                                <CButton preset="gray" icon="mdi:pencil" class="text-xs" @click="handleEditUser(user)">
                                    Edit
                                </CButton>

                                <CButton
                                    preset="danger"
                                    icon="mdi:delete"
                                    class="text-xs"
                                    @click="handleDeleteUser(user.id)"
                                >
                                    Delete
                                </CButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attach Existing User Modal -->
        <Teleport to="body">
            <div
                v-if="showAttachModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeAttachModal"
            >
                <div class="flex w-full max-w-md flex-col rounded-xl bg-white shadow-xl">
                    <!-- Header -->
                    <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-red-100">
                                <Icon icon="mdi:account-arrow-right" class="text-lg text-red-700" />
                            </div>

                            <h2 class="text-base font-semibold text-gray-900">Attach Existing User</h2>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                            @click="closeAttachModal"
                        >
                            <Icon icon="mdi:close" class="text-lg" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4">
                        <!-- Search -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Search user
                                <span class="text-red-500">*</span>
                            </label>

                            <CTypeahead
                                :refresh-options="searchAttachableUsers"
                                placeholder="Type name or email..."
                                empty-text="No users found"
                                clearable
                                @change="onAttachUserChange"
                            />
                        </div>

                        <!-- Role selector (only when there's already an admin) -->
                        <div v-if="clientUsers.some((u) => u.client_role === 'admin')">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Client Role</label>

                            <div class="flex gap-3">
                                <label
                                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border p-3 transition"
                                    :class="[
                                        {
                                            'border-red-500 bg-red-50': attachRole === 'admin',
                                            'border-gray-200 hover:border-gray-300 hover:bg-gray-50':
                                                attachRole !== 'admin',
                                        },
                                    ]"
                                    @click="attachRole = 'admin'"
                                >
                                    <div
                                        :class="[
                                            'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2',
                                            {
                                                'border-red-600 bg-red-600': attachRole === 'admin',
                                                'border-gray-300': attachRole !== 'admin',
                                            },
                                        ]"
                                    >
                                        <div
                                            v-if="attachRole === 'admin'"
                                            class="h-1.5 w-1.5 rounded-full bg-white"
                                        ></div>
                                    </div>

                                    <div>
                                        <span class="flex items-center gap-1 text-sm font-medium text-gray-800">
                                            <Icon icon="mdi:crown" class="text-red-600" />
                                            Admin
                                        </span>

                                        <p class="text-xs text-gray-500">Replaces current admin</p>
                                    </div>
                                </label>

                                <label
                                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border p-3 transition"
                                    :class="[
                                        {
                                            'border-red-500 bg-red-50': attachRole === 'member',
                                            'border-gray-200 hover:border-gray-300 hover:bg-gray-50':
                                                attachRole !== 'member',
                                        },
                                    ]"
                                    @click="attachRole = 'member'"
                                >
                                    <div
                                        :class="[
                                            'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2',
                                            {
                                                'border-red-600 bg-red-600': attachRole === 'member',
                                                'border-gray-300': attachRole !== 'member',
                                            },
                                        ]"
                                    >
                                        <div
                                            v-if="attachRole === 'member'"
                                            class="h-1.5 w-1.5 rounded-full bg-white"
                                        ></div>
                                    </div>

                                    <span class="text-sm font-medium text-gray-800">Member</span>
                                </label>
                            </div>
                        </div>

                        <div v-else class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-700">
                            <Icon icon="mdi:information-outline" class="mr-1 inline" />
                            This will be the first user, so they'll be set as
                            <strong>Admin</strong>
                            automatically.
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-between gap-2 pt-2">
                            <div class="flex">
                                <CButton preset="primary" icon="mdi:account-plus" @click="showCreateUserModal = true">
                                    New User
                                </CButton>
                            </div>

                            <div class="flex justify-end gap-2">
                                <CButton preset="outlined-black" type="button" @click="closeAttachModal">
                                    Cancel
                                </CButton>

                                <CButton
                                    preset="primary"
                                    icon="mdi:account-arrow-right"
                                    :disabled="!attachSelected || attachLoading"
                                    @click="handleAttachUser"
                                >
                                    {{ attachLoading ? 'Linking...' : 'Link User' }}
                                </CButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Create Wallet Modal -->
        <div v-if="showCreateWalletModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">New Wallet</h2>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                    <input
                        v-model="newWalletName"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                    />
                </div>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                        v-model="newWalletDescription"
                        rows="2"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                    ></textarea>
                </div>
                <div v-if="canViewInternalNote" class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Internal Note</label>
                    <textarea
                        v-model="newWalletInternalNote"
                        rows="2"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        placeholder="Visible only to authorized users"
                    ></textarea>
                </div>
                <div v-if="canUpdateRules" class="mb-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Allow credit purchases</p>
                            <p class="text-xs text-gray-500">Enable this wallet for selling credits.</p>
                        </div>

                        <button
                            type="button"
                            role="switch"
                            :aria-checked="newWalletCreditPurchaseAllowed"
                            :class="[
                                'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-red-500',
                                newWalletCreditPurchaseAllowed ? 'bg-red-600' : 'bg-gray-200',
                            ]"
                            @click="newWalletCreditPurchaseAllowed = !newWalletCreditPurchaseAllowed"
                        >
                            <span
                                :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200',
                                    newWalletCreditPurchaseAllowed ? 'translate-x-5' : 'translate-x-0',
                                ]"
                            />
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Currency Code
                        <span v-if="newWalletCreditPurchaseAllowed" class="text-red-600">*</span>
                    </label>
                    <select
                        v-model="newWalletCurrencyCode"
                        :class="[
                            'w-full rounded-lg border px-3 py-2 focus:outline-none focus:ring-1',
                            newWalletValidationErrors.currency_code
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50'
                                : 'border-gray-300 focus:border-red-500 focus:ring-red-500',
                        ]"
                    >
                        <option value="">Select currency...</option>
                        <option v-for="code in currencyCodes" :key="code" :value="code">
                            {{ code }}
                        </option>
                    </select>
                    <p v-if="newWalletValidationErrors.currency_code" class="mt-1 text-xs text-red-600">
                        {{ newWalletValidationErrors.currency_code }}
                    </p>
                </div>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Hourly Rate Reference
                        <span v-if="newWalletCreditPurchaseAllowed" class="text-red-600">*</span>
                        <span v-else class="text-gray-500">(optional)</span>
                    </label>
                    <input
                        v-model.number="newWalletHourlyRate"
                        type="number"
                        step="0.01"
                        min="0"
                        :class="[
                            'w-full rounded-lg border px-3 py-2 focus:outline-none focus:ring-1',
                            newWalletValidationErrors.hourly_rate_reference
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50'
                                : 'border-gray-300 focus:border-red-500 focus:ring-red-500',
                        ]"
                    />
                    <p v-if="newWalletValidationErrors.hourly_rate_reference" class="mt-1 text-xs text-red-600">
                        {{ newWalletValidationErrors.hourly_rate_reference }}
                    </p>
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
                        @click="showCreateWalletModal = false"
                    >
                        Cancel
                    </button>
                    <CButton :disabled="!isNewWalletFormValid" @click="handleCreateWallet">Create</CButton>
                </div>
            </div>
        </div>

        <!-- Edit Wallet Modal -->
        <div
            v-if="showEditWalletModal && editingWallet"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">Edit Wallet</h2>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                    <input
                        v-model="editingWallet.name"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                    />
                </div>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                        v-model="editingWallet.description"
                        rows="2"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                    ></textarea>
                </div>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Hourly Rate Reference (optional)</label>
                    <input
                        v-model.number="editingWallet.hourly_rate_reference"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
                        @click="showEditWalletModal = false"
                    >
                        Cancel
                    </button>
                    <CButton @click="handleUpdateWallet">Update</CButton>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateUserModal" class="fixed inset-0 z-60 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">Add User</h2>

                <div class="space-y-4">
                    <CInput v-model="userForm.name" label="Name" type="text" required />

                    <CInput v-model="userForm.email" label="Email" type="email" required />

                    <CInput v-model="userForm.password" label="Password" type="password" required />

                    <CInput
                        v-model="userForm.password_confirmation"
                        label="Confirm Password"
                        type="password"
                        required
                    />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <CButton preset="gray" @click="showCreateUserModal = false">Cancel</CButton>

                    <CButton
                        preset="primary"
                        @click="handleCreateUser"
                        :disabled="!userForm.name || !userForm.email || !userForm.password"
                    >
                        Create User
                    </CButton>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div
            v-if="showEditUserModal && editingUser"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">Edit User</h2>

                <div class="space-y-4">
                    <CInput v-model="editingUser.name" label="Name" type="text" required />

                    <CInput v-model="editingUser.email" label="Email" type="email" required />

                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                        <p class="text-sm text-yellow-800">
                            Leave the password fields blank if you don't want to change it
                        </p>
                    </div>

                    <CInput
                        v-model="editingUser.password"
                        label="New Password (optional)"
                        type="password"
                        placeholder="Leave blank to keep current password"
                    />

                    <CInput
                        v-if="editingUser.password"
                        v-model="editingUser.password_confirmation"
                        label="Confirm New Password"
                        type="password"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <CButton
                        preset="gray"
                        @click="
                            showEditUserModal = false;
                            editingUser = null;
                        "
                    >
                        Cancel
                    </CButton>

                    <CButton preset="primary" @click="handleUpdateUser">Save Changes</CButton>
                </div>
            </div>
        </div>
    </div>
</template>
