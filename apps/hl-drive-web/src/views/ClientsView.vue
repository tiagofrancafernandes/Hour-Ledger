<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { useClients } from '@/composables/useClients';
import { useClientUsers } from '@/composables/useClientUsers';
import { useUsers } from '@/composables/useUsers';
import { useRouter } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import type { Client, User, ClientUserForm } from '@/types';

const router = useRouter();
const toast = useToast();
const { clients, loading, error, pagination, fetchClients, createClient, updateClient, deleteClient } = useClients();
const {
    users: editClientUsers,
    loading: editUsersLoading,
    fetchClientUsers: fetchEditClientUsers,
    createClientUser,
    attachUser,
    setClientAdmin,
    deleteClientUser,
} = useClientUsers();
const { searchUsers } = useUsers();
const { canManageClients } = usePermissions();

// ─── Filters ────────────────────────────────────────────────────────────────

const showFilters = ref(true);

const filters = ref({
    search: '',
});

// ─── Debounced filter watch ────────────────────────────────────────────────

let filterDebounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    filters,
    () => {
        if (filterDebounceTimer !== null) {
            clearTimeout(filterDebounceTimer);
        }

        filterDebounceTimer = setTimeout(async () => {
            await fetchClients(1, filters.value.search);
        }, 400);
    },
    { deep: true }
);

const activeFiltersCount = computed(() => {
    let count = 0;

    if (filters.value.search) count++;

    return count;
});

// ─── List State ────────────────────────────────────────────────────────────

onMounted(() => {
    fetchClients();
});

function goToClient(id: number): void {
    router.push({ name: 'client-detail', params: { id } });
}

// ─── Create Client Modal ──────────────────────────────────────────────────────

const showCreateModal = ref(false);
const newClientName = ref('');
const newClientNotes = ref('');

async function handleCreate(): Promise<void> {
    if (!newClientName.value.trim()) {
        return;
    }

    try {
        await createClient({
            name: newClientName.value,
            notes: newClientNotes.value || undefined,
        });

        showCreateModal.value = false;
        newClientName.value = '';
        newClientNotes.value = '';
    } catch {
        // Error handled in composable
    }
}

// ─── Edit Client Modal (with tabs) ───────────────────────────────────────────

type EditTab = 'details' | 'users';

const showEditModal = ref(false);
const editTab = ref<EditTab>('details');
const editingClient = ref<Client | null>(null);

function handleEdit(client: Client): void {
    editingClient.value = { ...client };
    editTab.value = 'details';
    showEditModal.value = true;
    fetchEditClientUsers(client.id);

    showNewUserInline.value = false;
    showAttachInline.value = false;
}

function closeEditModal(): void {
    showEditModal.value = false;
    editingClient.value = null;
}

async function handleUpdate(): Promise<void> {
    if (!editingClient.value || !editingClient.value.name.trim()) {
        return;
    }

    try {
        await updateClient(editingClient.value.id, {
            name: editingClient.value.name,
            notes: editingClient.value.notes || undefined,
        });

        toast.success('Client updated');
        closeEditModal();
    } catch {
        toast.error('Failed to update client');
    }
}

async function handleDelete(id: number): Promise<void> {
    if (confirm('Are you sure you want to delete this client?')) {
        await deleteClient(id);
    }
}

// ─── Edit Modal: New User Inline ─────────────────────────────────────────────

const showNewUserInline = ref(false);
const newUserLoading = ref(false);
const newUserForm = ref<ClientUserForm>({
    client_id: 0,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function openNewUserInline(): void {
    showAttachInline.value = false;
    newUserForm.value = {
        client_id: editingClient.value?.id ?? 0,
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    };
    showNewUserInline.value = true;
}

async function handleCreateClientUser(): Promise<void> {
    if (!editingClient.value) {
        return;
    }

    newUserLoading.value = true;

    try {
        await createClientUser(editingClient.value.id, newUserForm.value);

        showNewUserInline.value = false;
        toast.success('User created successfully');
    } catch (e: any) {
        const msg = e?.response?.data?.message ?? e?.response?.data?.errors;

        toast.error(msg ? String(msg) : 'Failed to create user');
    } finally {
        newUserLoading.value = false;
    }
}

// ─── Edit Modal: Attach Existing User Inline ─────────────────────────────────

const showAttachInline = ref(false);
const attachSearch = ref('');
const attachResults = ref<User[] | undefined>([]);
const attachSearchLoading = ref(false);
const attachSelected = ref<User | null>(null);
const attachRole = ref<'admin' | 'member'>('member');
const attachLoading = ref(false);
const attachSearchTimer = ref<ReturnType<typeof setTimeout> | null>(null);

function openAttachInline(): void {
    showNewUserInline.value = false;
    attachSearch.value = '';
    attachResults.value = [];
    attachSelected.value = null;
    attachRole.value = 'member';
    showAttachInline.value = true;
}

async function onAttachSearchInput(): Promise<void> {
    if (attachSearchTimer.value) {
        clearTimeout(attachSearchTimer.value);
    }

    if (!attachSearch.value.trim()) {
        attachResults.value = [];

        return;
    }

    attachSearchTimer.value = setTimeout(async () => {
        attachSearchLoading.value = true;

        try {
            attachResults.value = await searchUsers(attachSearch.value.trim());
        } catch {
            // ignore
        } finally {
            attachSearchLoading.value = false;
        }
    }, 350);
}

function selectAttachUser(user: User): void {
    attachSelected.value = user;
    attachSearch.value = user.name;
    attachResults.value = [];
}

async function handleAttachUser(): Promise<void> {
    if (!editingClient.value || !attachSelected.value) {
        return;
    }

    const hasAdmin = editClientUsers.value.some((u) => u.client_role === 'admin');
    const role = hasAdmin ? attachRole.value : 'admin';

    attachLoading.value = true;

    try {
        await attachUser(editingClient.value.id, attachSelected.value.id, role);

        showAttachInline.value = false;
        toast.success('User linked successfully');
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to link user');
    } finally {
        attachLoading.value = false;
    }
}

// ─── Edit Modal: User Actions ─────────────────────────────────────────────────

async function handleSetClientAdmin(userId: number): Promise<void> {
    if (!editingClient.value) {
        return;
    }

    try {
        await setClientAdmin(editingClient.value.id, userId);

        toast.success('Admin updated successfully');
    } catch {
        toast.error('Failed to update admin');
    }
}

async function handleRemoveClientUser(userId: number): Promise<void> {
    if (!editingClient.value || !confirm('Remove this user from the client?')) {
        return;
    }

    try {
        await deleteClientUser(editingClient.value.id, userId);

        toast.success('User removed');
    } catch {
        toast.error('Failed to remove user');
    }
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Page header with filter toggle -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your clients.</p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    @click="showFilters = !showFilters"
                    :class="[
                        'inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium transition-colors',
                        {
                            'bg-red-50 border-red-200 text-red-700': activeFiltersCount > 0,
                            'bg-white border-gray-300 text-gray-700 hover:bg-gray-50': activeFiltersCount === 0,
                        },
                    ]"
                >
                    <Icon icon="mdi:filter-outline" class="w-4 h-4" />
                    Filters
                    <span
                        v-if="activeFiltersCount > 0"
                        class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-600 text-white text-xs font-bold"
                    >
                        {{ activeFiltersCount }}
                    </span>
                </button>

                <CButton v-if="canManageClients" preset="primary" icon="mdi:plus" @click="showCreateModal = true">
                    New Client
                </CButton>
            </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ error }}
        </div>

        <!-- Filter panel -->
        <div v-if="showFilters" class="mb-5 rounded-xl border border-gray-200 bg-white shadow-sm p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Search -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Search</label>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search by name or notes..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    />
                    <p class="mt-1 text-xs text-gray-500">Searches across client name and notes</p>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="py-12 text-center text-sm text-gray-500">
            <Icon icon="heroicons:arrow-path" class="w-5 h-5 animate-spin mx-auto mb-2 text-gray-400" />
            Loading...
        </div>

        <!-- Table -->
        <div v-else class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Notes
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr
                        v-for="client in clients"
                        :key="client.id"
                        class="cursor-pointer hover:bg-gray-50 transition-colors"
                        @click="goToClient(client.id)"
                    >
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">
                            {{ client.name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            {{ client.notes || '—' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <div v-if="canManageClients" class="flex justify-end gap-1">
                                <button
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition-colors"
                                    @click.stop="handleEdit(client)"
                                >
                                    <Icon icon="heroicons:pencil-square" class="w-3.5 h-3.5" />
                                    Edit
                                </button>
                                <button
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 transition-colors"
                                    @click.stop="handleDelete(client.id)"
                                >
                                    <Icon icon="heroicons:trash" class="w-3.5 h-3.5" />
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="!clients.length">
                        <td colspan="3" class="px-6 py-12 text-center">
                            <Icon icon="heroicons:users" class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                            <p class="text-sm font-medium text-gray-500">No clients found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="mt-4 flex items-center justify-center gap-2">
            <button
                :disabled="pagination.currentPage === 1"
                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                @click="fetchClients(pagination.currentPage - 1, filters.search)"
            >
                <Icon icon="heroicons:chevron-left" class="w-4 h-4" />
                Previous
            </button>
            <span class="text-sm text-gray-500">Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
            <button
                :disabled="pagination.currentPage === pagination.lastPage"
                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                @click="fetchClients(pagination.currentPage + 1, filters.search)"
            >
                Next
                <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
            </button>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="showCreateModal = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-900">New Client</h2>
                        <button
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            @click="showCreateModal = false"
                        >
                            <Icon icon="heroicons:x-mark" class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                            <input
                                v-model="newClientName"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors"
                                placeholder="Client name"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                            <textarea
                                v-model="newClientNotes"
                                rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors resize-none"
                                placeholder="Optional notes..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 rounded-b-2xl border-t border-gray-100">
                        <CButton preset="lightgray-md" @click="showCreateModal = false">Cancel</CButton>
                        <CButton preset="primary-md" @click="handleCreate">Create Client</CButton>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Modal (tabbed) -->
        <Teleport to="body">
            <div
                v-if="showEditModal && editingClient"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="closeEditModal"
            >
                <div class="flex w-full max-w-lg flex-col rounded-2xl bg-white shadow-xl max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="flex shrink-0 items-center justify-between px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                <Icon icon="mdi:domain" class="text-gray-600" />
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">{{ editingClient.name }}</h2>
                        </div>

                        <button
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            @click="closeEditModal"
                        >
                            <Icon icon="heroicons:x-mark" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="flex shrink-0 border-b border-gray-100">
                        <button
                            v-for="tab in ['details', 'users'] as const"
                            :key="tab"
                            type="button"
                            class="px-6 py-3 text-sm font-medium transition-colors"
                            :class="[
                                {
                                    'border-b-2 border-red-600 text-red-600': editTab === tab,
                                    'text-gray-500 hover:text-gray-800': editTab !== tab,
                                },
                            ]"
                            @click="editTab = tab"
                        >
                            <span v-if="tab === 'details'" class="flex items-center gap-1.5">
                                <Icon icon="mdi:text-box-outline" class="text-base" />
                                Details
                            </span>
                            <span v-else class="flex items-center gap-1.5">
                                <Icon icon="mdi:account-group-outline" class="text-base" />
                                Users
                                <span
                                    v-if="editClientUsers.length > 0"
                                    class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full text-[10px] font-bold"
                                    :class="[
                                        {
                                            'bg-red-600 text-white': editTab === 'users',
                                            'bg-gray-200 text-gray-600': editTab !== 'users',
                                        },
                                    ]"
                                >
                                    {{ editClientUsers.length }}
                                </span>
                            </span>
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="overflow-y-auto flex-1">
                        <!-- ── Details Tab ── -->
                        <div v-if="editTab === 'details'" class="px-6 py-5 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                                <input
                                    v-model="editingClient.name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                                <textarea
                                    v-model="editingClient.notes"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors resize-none"
                                ></textarea>
                            </div>

                            <div class="flex justify-end gap-2 pt-1">
                                <CButton preset="lightgray-md" @click="closeEditModal">Cancel</CButton>
                                <CButton preset="primary-md" @click="handleUpdate">Save Changes</CButton>
                            </div>
                        </div>

                        <!-- ── Users Tab ── -->
                        <div v-else class="flex flex-col">
                            <!-- Action bar -->
                            <div
                                class="flex shrink-0 items-center justify-between gap-2 border-b border-gray-100 px-5 py-3"
                            >
                                <p class="text-xs text-gray-500">
                                    {{ editClientUsers.length }} user{{ editClientUsers.length !== 1 ? 's' : '' }}
                                </p>

                                <div class="flex gap-2">
                                    <button
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                        :class="[
                                            {
                                                'bg-gray-100 border-gray-300': showAttachInline,
                                            },
                                        ]"
                                        @click="openAttachInline"
                                    >
                                        <Icon icon="mdi:account-arrow-right" class="text-sm" />
                                        Attach Existing
                                    </button>

                                    <CButton preset="primary-md" icon="mdi:account-plus" @click="openNewUserInline">
                                        New User
                                    </CButton>
                                </div>
                            </div>

                            <!-- Attach Existing inline form -->
                            <div
                                v-if="showAttachInline"
                                class="border-b border-gray-100 bg-gray-50 px-5 py-4 space-y-3"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    Attach existing user
                                </p>

                                <!-- Search -->
                                <div class="relative">
                                    <input
                                        v-model="attachSearch"
                                        type="text"
                                        placeholder="Search by name or email..."
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                        @input="onAttachSearchInput"
                                    />
                                    <div v-if="attachSearchLoading" class="absolute top-1/2 right-3 -translate-y-1/2">
                                        <div
                                            class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-gray-300 border-t-red-600"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Dropdown results -->
                                <div
                                    v-if="(attachResults || []).length > 0"
                                    class="max-h-36 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-md"
                                >
                                    <button
                                        v-for="result in attachResults"
                                        :key="result.id"
                                        type="button"
                                        class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm transition hover:bg-gray-50"
                                        @click="selectAttachUser(result)"
                                    >
                                        <div
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700"
                                        >
                                            {{ result.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 truncate">{{ result.name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ result.email }}</p>
                                        </div>
                                        <span
                                            v-if="result.customer_id"
                                            class="ml-auto shrink-0 rounded-full bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-700"
                                        >
                                            Assigned
                                        </span>
                                    </button>
                                </div>

                                <!-- Selected user chip -->
                                <div
                                    v-if="attachSelected"
                                    class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2"
                                >
                                    <Icon icon="mdi:account-check" class="shrink-0 text-red-600" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ attachSelected.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 truncate">{{ attachSelected.email }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="shrink-0 text-gray-400 hover:text-gray-600"
                                        @click="
                                            attachSelected = null;
                                            attachSearch = '';
                                        "
                                    >
                                        <Icon icon="mdi:close" class="text-sm" />
                                    </button>
                                </div>

                                <!-- Role selector (only when there's already an admin) -->
                                <div v-if="attachSelected && editClientUsers.some((u) => u.client_role === 'admin')">
                                    <p class="mb-1.5 text-xs font-medium text-gray-600">Client Role</p>
                                    <div class="flex gap-2">
                                        <label
                                            v-for="roleOpt in ['admin', 'member'] as const"
                                            :key="roleOpt"
                                            class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm transition"
                                            :class="[
                                                {
                                                    'border-red-500 bg-red-50': attachRole === roleOpt,
                                                    'border-gray-200 hover:border-gray-300': attachRole !== roleOpt,
                                                },
                                            ]"
                                            @click="attachRole = roleOpt"
                                        >
                                            <div
                                                :class="[
                                                    'flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full border-2',
                                                    {
                                                        'border-red-600 bg-red-600': attachRole === roleOpt,
                                                        'border-gray-300': attachRole !== roleOpt,
                                                    },
                                                ]"
                                            >
                                                <div
                                                    v-if="attachRole === roleOpt"
                                                    class="h-1 w-1 rounded-full bg-white"
                                                ></div>
                                            </div>
                                            <span class="flex items-center gap-1 font-medium text-gray-800">
                                                <Icon
                                                    v-if="roleOpt === 'admin'"
                                                    icon="mdi:crown"
                                                    class="text-xs text-red-500"
                                                />
                                                {{ roleOpt === 'admin' ? 'Admin' : 'Member' }}
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div
                                    v-else-if="attachSelected"
                                    class="flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700"
                                >
                                    <Icon icon="mdi:information-outline" class="shrink-0" />
                                    First user — will be set as
                                    <strong class="ml-0.5">Admin</strong>
                                    automatically.
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button
                                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors"
                                        @click="showAttachInline = false"
                                    >
                                        Cancel
                                    </button>
                                    <CButton
                                        preset="primary-md"
                                        icon="mdi:account-arrow-right"
                                        :disabled="!attachSelected || attachLoading"
                                        @click="handleAttachUser"
                                    >
                                        {{ attachLoading ? 'Linking...' : 'Link User' }}
                                    </CButton>
                                </div>
                            </div>

                            <!-- New User inline form -->
                            <div
                                v-if="showNewUserInline"
                                class="border-b border-gray-100 bg-gray-50 px-5 py-4 space-y-3"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">New user</p>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600">Name *</label>
                                        <CInput v-model="newUserForm.name" placeholder="Full name" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600">Email *</label>
                                        <CInput v-model="newUserForm.email" type="email" placeholder="email@..." />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600">Password *</label>
                                        <CInput
                                            v-model="newUserForm.password"
                                            type="password"
                                            placeholder="Min. 8 chars"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600">Confirm *</label>
                                        <CInput
                                            v-model="newUserForm.password_confirmation"
                                            type="password"
                                            placeholder="Repeat password"
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button
                                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors"
                                        @click="showNewUserInline = false"
                                    >
                                        Cancel
                                    </button>
                                    <CButton
                                        preset="primary-md"
                                        icon="mdi:account-plus"
                                        :disabled="
                                            !newUserForm.name ||
                                            !newUserForm.email ||
                                            !newUserForm.password ||
                                            newUserLoading
                                        "
                                        @click="handleCreateClientUser"
                                    >
                                        {{ newUserLoading ? 'Creating...' : 'Create User' }}
                                    </CButton>
                                </div>
                            </div>

                            <!-- Users list -->
                            <div v-if="editUsersLoading" class="flex items-center justify-center py-10">
                                <div
                                    class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-red-600"
                                ></div>
                            </div>

                            <div
                                v-else-if="editClientUsers.length === 0"
                                class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center"
                            >
                                <Icon icon="mdi:account-group-outline" class="text-3xl text-gray-300" />
                                <p class="text-sm text-gray-500">No users yet</p>
                                <p class="text-xs text-gray-400">Use the buttons above to add users.</p>
                            </div>

                            <ul v-else class="divide-y divide-gray-100">
                                <li
                                    v-for="user in editClientUsers"
                                    :key="user.id"
                                    class="flex items-center gap-3 px-5 py-3.5"
                                >
                                    <!-- Avatar -->
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-700"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>

                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>

                                            <span
                                                v-if="user.client_role === 'admin'"
                                                class="inline-flex items-center gap-0.5 rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700"
                                            >
                                                <Icon icon="mdi:crown" class="text-[10px]" />
                                                Admin
                                            </span>

                                            <span
                                                v-else-if="user.client_role === 'member'"
                                                class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-500"
                                            >
                                                Member
                                            </span>
                                        </div>

                                        <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button
                                            v-if="user.client_role !== 'admin'"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-100"
                                            :title="'Set ' + user.name + ' as admin'"
                                            @click="handleSetClientAdmin(user.id)"
                                        >
                                            <Icon icon="mdi:crown-outline" class="text-sm" />
                                            Set Admin
                                        </button>

                                        <button
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-red-500 transition-colors hover:bg-red-50"
                                            @click="handleRemoveClientUser(user.id)"
                                        >
                                            <Icon icon="mdi:account-remove" class="text-sm" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
