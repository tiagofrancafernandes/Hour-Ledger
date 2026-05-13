<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { useUsers } from '@/composables/useUsers';
import { useClients } from '@/composables/useClients';
import { useToast } from '@/composables/useToast';
import type { User, Client, CreateUserForm } from '@/types';
import type { UserDetail, UserOptions } from '@/composables/useUsers';

const toast = useToast();
const {
    users,
    loading,
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    updateUserRole,
    updateUserPermissions,
    fetchOptions,
    attachUserToClient,
    setUserClientAdmin,
    updateUserPassword,
} = useUsers();
const { searchClients } = useClients();

// ─── Pagination & Search ─────────────────────────────────────────────────────

const search = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const searchTimer = ref<ReturnType<typeof setTimeout> | null>(null);

async function loadUsers(page = 1): Promise<void> {
    const params: Record<string, unknown> = { page };

    if (search.value.trim()) {
        params.search = search.value.trim();
    }

    const response = await fetchUsers(params);

    currentPage.value = response.current_page;
    totalPages.value = response.last_page;
}

watch(search, () => {
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
    }

    searchTimer.value = setTimeout(() => {
        loadUsers(1);
    }, 350);
});

// ─── Available Options (roles + all permissions) ─────────────────────────────

const options = ref<UserOptions>({ roles: [], permissions: [] });

const permissionGroups = computed(() => {
    const groups: Record<string, string[]> = {};

    options.value.permissions.forEach((perm) => {
        const namespace = perm.split('.')[0] ?? perm;

        if (!groups[namespace]) {
            groups[namespace] = [];
        }

        groups[namespace].push(perm);
    });

    return groups;
});

// ─── Modal State ─────────────────────────────────────────────────────────────

type ModalTab = 'info' | 'role' | 'permissions' | 'client' | 'password';

const showModal = ref(false);
const activeTab = ref<ModalTab | string>('info');
const selectedUserDetail = ref<UserDetail | null>(null);
const modalLoading = ref(false);

const infoForm = ref({ name: '', email: '' });
const infoErrors = ref<Record<string, string>>({});

const roleForm = ref('');

const selectedPermissions = ref<string[]>([]);

const passwordForm = ref({ password: '', password_confirmation: '' });
const passwordErrors = ref<Record<string, string>>({});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function getRoleColor(role: string): string {
    const colors: Record<string, string> = {
        super_admin: 'bg-purple-100 text-purple-800',
        admin: 'bg-red-100 text-red-800',
        manager: 'bg-blue-100 text-blue-800',
        operator: 'bg-yellow-100 text-yellow-800',
        customer: 'bg-green-100 text-green-800',
    };

    return colors[role] ?? 'bg-gray-100 text-gray-700';
}

const currentSelectedRole = computed(() => {
    if (!selectedUserDetail.value || !selectedUserDetail.value?.user) {
        return false;
    }

    let selectedUserRoles: any[] = (selectedUserDetail.value || {})?.user?.roles || [];

    return selectedUserRoles[0]?.name || null;
});

function isCurrentRole(role: string | null = null): boolean {
    if (!role) {
        return false;
    }

    if (!selectedUserDetail.value || !selectedUserDetail.value?.user) {
        return false;
    }

    let selectedUserRoles: any[] = (selectedUserDetail.value || {})?.user?.roles || [];

    if (!Array.isArray(selectedUserRoles) || !selectedUserRoles.length) {
        return false;
    }

    return selectedUserRoles[0]?.name === role;
}

function getRoleLabel(role: string): string {
    const labels: Record<string, string> = {
        super_admin: 'Super Admin',
        admin: 'Admin',
        manager: 'Manager',
        operator: 'Operator',
        customer: 'Customer',
    };

    return labels[role] ?? role;
}

function getUserRole(user: User): string {
    const roles = (user as any).roles as Array<{ name: string }> | undefined;

    if (roles && roles.length > 0) {
        return roles[0]?.name ?? '-';
    }

    return user.role ?? '-';
}

// ─── Modal Open / Close ───────────────────────────────────────────────────────

async function openModal(user: User): Promise<void> {
    showModal.value = true;
    activeTab.value = 'info';
    modalLoading.value = true;
    selectedUserDetail.value = null;
    infoErrors.value = {};
    passwordErrors.value = {};
    passwordForm.value = { password: '', password_confirmation: '' };

    try {
        const detail = await fetchUser(user.id);

        selectedUserDetail.value = detail;
        infoForm.value = { name: detail.user.name, email: detail.user.email };
        roleForm.value = getUserRole(detail.user);
        selectedPermissions.value = [...detail.direct_permissions];
    } catch {
        toast.error('Failed to load user details');
        showModal.value = false;
    } finally {
        modalLoading.value = false;
    }
}

function closeModal(): void {
    showModal.value = false;
    selectedUserDetail.value = null;
}

// ─── Save Info ────────────────────────────────────────────────────────────────

async function handleSaveInfo(): Promise<void> {
    if (!selectedUserDetail.value) {
        return;
    }

    infoErrors.value = {};

    if (!infoForm.value.name.trim()) {
        infoErrors.value.name = 'Name is required';

        return;
    }

    if (!infoForm.value.email.trim()) {
        infoErrors.value.email = 'Email is required';

        return;
    }

    modalLoading.value = true;

    try {
        const updated = await updateUser(selectedUserDetail.value.user.id, {
            name: infoForm.value.name.trim(),
            email: infoForm.value.email.trim(),
        });

        selectedUserDetail.value.user = updated;

        const userInList = users.value.find((u) => u.id === updated.id);

        if (userInList) {
            userInList.name = updated.name;
            userInList.email = updated.email;
        }

        toast.success('User updated successfully');
    } catch (e: any) {
        const errors = e?.response?.data?.errors;

        if (errors) {
            Object.keys(errors).forEach((field) => {
                infoErrors.value[field] = errors[field][0];
            });
        } else {
            toast.error('Failed to update user');
        }
    } finally {
        modalLoading.value = false;
    }
}

// ─── Save Role ────────────────────────────────────────────────────────────────

async function handleSaveRole(): Promise<void> {
    if (!selectedUserDetail.value || !roleForm.value) {
        return;
    }

    modalLoading.value = true;

    try {
        const updated = await updateUserRole(selectedUserDetail.value.user.id, roleForm.value);

        selectedUserDetail.value.user = updated;

        const userInList = users.value.find((u) => u.id === updated.id);

        if (userInList) {
            (userInList as any).roles = (updated as any).roles;
        }

        toast.success('Role updated successfully');
    } catch {
        toast.error('Failed to update role');
    } finally {
        modalLoading.value = false;
    }
}

// ─── Permissions Helpers ──────────────────────────────────────────────────────

function togglePermission(perm: string): void {
    const idx = selectedPermissions.value.indexOf(perm);

    if (idx >= 0) {
        selectedPermissions.value.splice(idx, 1);
    } else {
        selectedPermissions.value.push(perm);
    }
}

function toggleGroup(groupPermissions: string[]): void {
    const allSelected = groupPermissions.every((p) => selectedPermissions.value.includes(p));

    if (allSelected) {
        selectedPermissions.value = selectedPermissions.value.filter((p) => !groupPermissions.includes(p));
    } else {
        groupPermissions.forEach((p) => {
            if (!selectedPermissions.value.includes(p)) {
                selectedPermissions.value.push(p);
            }
        });
    }
}

function isGroupFullySelected(groupPermissions: string[]): boolean {
    return groupPermissions.every((p) => selectedPermissions.value.includes(p));
}

function isGroupPartiallySelected(groupPermissions: string[]): boolean {
    const someSelected = groupPermissions.some((p) => selectedPermissions.value.includes(p));

    return someSelected && !isGroupFullySelected(groupPermissions);
}

// ─── Save Permissions ─────────────────────────────────────────────────────────

async function handleSavePermissions(): Promise<void> {
    if (!selectedUserDetail.value) {
        return;
    }

    modalLoading.value = true;

    try {
        const updated = await updateUserPermissions(selectedUserDetail.value.user.id, selectedPermissions.value);

        selectedUserDetail.value.direct_permissions = updated;

        toast.success('Permissions updated successfully');
    } catch {
        toast.error('Failed to update permissions');
    } finally {
        modalLoading.value = false;
    }
}

// ─── Save Password ─────────────────────────────────────────────────────────────

async function handleSavePassword(): Promise<void> {
    if (!selectedUserDetail.value) {
        return;
    }

    passwordErrors.value = {};

    if (!passwordForm.value.password.trim()) {
        passwordErrors.value.password = 'Password is required';

        return;
    }

    if (passwordForm.value.password.length < 8) {
        passwordErrors.value.password = 'Password must be at least 8 characters';

        return;
    }

    if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
        passwordErrors.value.password_confirmation = 'Passwords do not match';

        return;
    }

    modalLoading.value = true;

    try {
        await updateUserPassword(selectedUserDetail.value.user.id, {
            password: passwordForm.value.password,
            password_confirmation: passwordForm.value.password_confirmation,
        });

        passwordForm.value = { password: '', password_confirmation: '' };
        toast.success('Password updated successfully');
    } catch (e: any) {
        const errors = e?.response?.data?.errors;

        if (errors) {
            Object.keys(errors).forEach((field) => {
                passwordErrors.value[field] = errors[field][0];
            });
        } else {
            toast.error('Failed to update password');
        }
    } finally {
        modalLoading.value = false;
    }
}

// ─── Edit Modal: Client Tab ───────────────────────────────────────────────────

const editClientSearch = ref('');
const editClientResults = ref<Client[]>([]);
const editClientSearchLoading = ref(false);
const editClientSelected = ref<Client | null>(null);
const editClientRole = ref<'admin' | 'member'>('member');
const editClientActionLoading = ref(false);

function onEditClientSearchInput(): void {
    runClientSearch(editClientSearch.value, editClientResults, editClientSearchLoading);
}

function selectEditClient(client: Client): void {
    editClientSelected.value = client;
    editClientSearch.value = client.name;
    editClientResults.value = [];
}

async function handleAssignClient(): Promise<void> {
    if (!selectedUserDetail.value || !editClientSelected.value) {
        return;
    }

    editClientActionLoading.value = true;

    try {
        const updated = await attachUserToClient(
            editClientSelected.value.id,
            selectedUserDetail.value.user.id,
            editClientRole.value
        );

        selectedUserDetail.value.user = updated;

        const userInList = users.value.find((u) => u.id === updated.id);

        if (userInList) {
            userInList.customer_id = updated.customer_id;
            userInList.client_role = updated.client_role;
            (userInList as any).client = (updated as any).client;
        }

        editClientSearch.value = '';
        editClientResults.value = [];
        editClientSelected.value = null;

        toast.success('Client assigned successfully');
    } catch (e: any) {
        const msg = e?.response?.data?.message;

        toast.error(msg ?? 'Failed to assign client');
    } finally {
        editClientActionLoading.value = false;
    }
}

async function handleSetAdminInEdit(): Promise<void> {
    if (!selectedUserDetail.value?.user.customer_id) {
        return;
    }

    editClientActionLoading.value = true;

    try {
        const updated = await setUserClientAdmin(
            selectedUserDetail.value.user.customer_id,
            selectedUserDetail.value.user.id
        );

        selectedUserDetail.value.user = updated;

        toast.success('Set as client admin successfully');
    } catch {
        toast.error('Failed to set as admin');
    } finally {
        editClientActionLoading.value = false;
    }
}

// ─── Client Search (shared typeahead state) ───────────────────────────────────

const clientSearchTimer = ref<ReturnType<typeof setTimeout> | null>(null);

async function runClientSearch(
    query: string,
    resultRef: { value: Client[] },
    loadingRef: { value: boolean }
): Promise<void> {
    if (clientSearchTimer.value) {
        clearTimeout(clientSearchTimer.value);
    }

    if (!query.trim()) {
        resultRef.value = [];

        return;
    }

    clientSearchTimer.value = setTimeout(async () => {
        loadingRef.value = true;

        try {
            resultRef.value = await searchClients(query.trim());
        } catch {
            // ignore
        } finally {
            loadingRef.value = false;
        }
    }, 350);
}

// ─── Create User Modal ────────────────────────────────────────────────────────

const showCreateModal = ref(false);
const createLoading = ref(false);
const createErrors = ref<Record<string, string>>({});

const createClientSearch = ref('');
const createClientResults = ref<Client[]>([]);
const createClientSearchLoading = ref(false);
const createClientSelected = ref<Client | null>(null);

const createForm = ref<CreateUserForm>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'customer',
    customer_id: null,
    client_role: 'member',
});

function resetCreateForm(): void {
    createForm.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'customer',
        customer_id: null,
        client_role: 'member',
    };

    createClientSearch.value = '';
    createClientResults.value = [];
    createClientSelected.value = null;
    createErrors.value = {};
}

function onCreateClientSearchInput(): void {
    runClientSearch(createClientSearch.value, createClientResults, createClientSearchLoading);
}

function selectCreateClient(client: Client): void {
    createClientSelected.value = client;
    createForm.value.customer_id = client.id;
    createClientSearch.value = client.name;
    createClientResults.value = [];
}

function clearCreateClient(): void {
    createClientSelected.value = null;
    createForm.value.customer_id = null;
    createClientSearch.value = '';
}

async function handleCreateUser(): Promise<void> {
    createErrors.value = {};

    if (!createForm.value.name.trim()) {
        createErrors.value.name = 'Name is required';

        return;
    }

    if (!createForm.value.email.trim()) {
        createErrors.value.email = 'Email is required';

        return;
    }

    if (!createForm.value.password) {
        createErrors.value.password = 'Password is required';

        return;
    }

    if (createForm.value.password !== createForm.value.password_confirmation) {
        createErrors.value.password_confirmation = 'Passwords do not match';

        return;
    }

    createLoading.value = true;

    try {
        await createUser({
            name: createForm.value.name.trim(),
            email: createForm.value.email.trim(),
            password: createForm.value.password,
            password_confirmation: createForm.value.password_confirmation,
            role: createForm.value.role,
            customer_id: createForm.value.customer_id,
            client_role: createForm.value.customer_id ? createForm.value.client_role : undefined,
        });

        showCreateModal.value = false;
        resetCreateForm();
        toast.success('User created successfully');
    } catch (e: any) {
        const errors = e?.response?.data?.errors;

        if (errors) {
            Object.keys(errors).forEach((field) => {
                createErrors.value[field] = errors[field][0];
            });
        } else {
            toast.error('Failed to create user');
        }
    } finally {
        createLoading.value = false;
    }
}

const allowedTabs = computed(() => {
    let _currentSelectedRole = currentSelectedRole.value || null;

    const tabs = ['info', 'role', 'permissions'];

    if (_currentSelectedRole && ['customer'].includes(_currentSelectedRole)) {
        tabs.push('client');
    }

    // Always show password tab (authorization check is done on backend)
    tabs.push('password');

    return tabs;
});

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(async () => {
    await Promise.all([
        loadUsers(),
        fetchOptions().then((data) => {
            options.value = data;
        }),
    ]);
});
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <UIPageHeader title="Users" description="Manage system users, roles, and permissions.">
            <template v-slot:actions>
                <CButton preset="primary" icon="mdi:account-plus" @click="showCreateModal = true">New User</CButton>

                <div class="relative">
                    <Icon icon="mdi:magnify" class="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400" />

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search users..."
                        class="w-64 rounded-lg border border-gray-300 py-2 pr-4 pl-9 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                    />
                </div>
            </template>
        </UIPageHeader>

        <!-- Initial loading -->
        <div v-if="loading && users.length === 0" class="flex justify-center py-16">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-red-600"></div>
        </div>

        <!-- Table -->
        <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Reload bar -->
            <div v-if="loading" class="h-0.5 w-full bg-gray-100">
                <div class="h-full w-1/3 animate-pulse bg-red-500"></div>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Client</th>
                        <th class="px-5 py-3">Member since</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <!-- Empty -->
                    <tr v-if="users.length === 0">
                        <td colspan="5" class="px-5 py-12 text-center text-gray-400">
                            <Icon icon="mdi:account-group-outline" class="mx-auto mb-2 text-4xl" />
                            <p>No users found</p>
                        </td>
                    </tr>

                    <tr v-for="user in users" :key="user.id" class="transition hover:bg-gray-50">
                        <!-- User -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-700"
                                >
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>

                                <div>
                                    <p class="font-medium text-gray-900">{{ user.name }}</p>
                                    <p class="text-xs text-gray-500">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Role -->
                        <td class="px-5 py-3.5">
                            <span
                                :class="[
                                    'inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    getRoleColor(getUserRole(user)),
                                ]"
                            >
                                {{ getRoleLabel(getUserRole(user)) }}
                            </span>
                        </td>

                        <!-- Client -->
                        <td class="px-5 py-3.5">
                            <div v-if="user.client?.name" class="flex items-center gap-1.5">
                                <span class="text-gray-700">{{ user.client.name }}</span>

                                <span
                                    v-if="user.client_role === 'admin'"
                                    class="inline-flex items-center gap-0.5 rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-700"
                                >
                                    <Icon icon="mdi:crown" class="text-[10px]" />
                                    Admin
                                </span>

                                <span
                                    v-else-if="user.client_role === 'member'"
                                    class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs font-semibold text-gray-500"
                                >
                                    Member
                                </span>
                            </div>

                            <span v-else class="text-gray-400">—</span>
                        </td>

                        <!-- Date -->
                        <td class="px-5 py-3.5 text-gray-500">
                            {{ user.created_at ? new Date(user.created_at).toLocaleDateString('pt-BR') : '-' }}
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-3.5 text-right">
                            <CButton
                                preset="outlined-black"
                                class="text-xs"
                                icon="mdi:pencil-outline"
                                @click="openModal(user)"
                            >
                                Edit
                            </CButton>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-gray-200 px-5 py-3">
                <p class="text-xs text-gray-500">Page {{ currentPage }} of {{ totalPages }}</p>

                <div class="flex gap-2">
                    <CButton
                        preset="outlined-black"
                        class="text-xs"
                        :disabled="currentPage <= 1"
                        icon="mdi:chevron-left"
                        @click="loadUsers(currentPage - 1)"
                    />

                    <CButton
                        preset="outlined-black"
                        class="text-xs"
                        :disabled="currentPage >= totalPages"
                        right-icon="mdi:chevron-right"
                        @click="loadUsers(currentPage + 1)"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeModal"
        >
            <div class="flex w-full max-w-2xl flex-col rounded-xl bg-white shadow-xl">
                <!-- Modal Header -->
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-700"
                        >
                            {{ selectedUserDetail?.user.name.charAt(0).toUpperCase() ?? '?' }}
                        </div>

                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ selectedUserDetail?.user.name ?? 'Loading...' }}
                            </h2>

                            <p class="text-xs text-gray-500">{{ selectedUserDetail?.user.email }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                        @click="closeModal"
                    >
                        <Icon icon="mdi:close" class="text-lg" />
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex shrink-0 border-b border-gray-200">
                    <button
                        v-for="tab in allowedTabs"
                        :key="tab"
                        type="button"
                        :class="[
                            'px-6 py-3 text-sm font-medium transition-colors',
                            {
                                'border-b-2 border-red-600 text-red-600': activeTab === tab,
                                'text-gray-500 hover:text-gray-800': activeTab !== tab,
                            },
                        ]"
                        @click="activeTab = tab"
                    >
                        <span v-if="tab === 'info'">Basic Info</span>
                        <span v-else-if="tab === 'role'">Role</span>
                        <span v-else-if="tab === 'client'">Client</span>
                        <span v-else-if="tab === 'password'">Password</span>
                        <span v-else>Permissions</span>
                    </button>
                </div>

                <!-- Loading initial detail -->
                <div v-if="modalLoading && !selectedUserDetail" class="flex items-center justify-center py-16">
                    <div class="h-7 w-7 animate-spin rounded-full border-4 border-gray-300 border-t-red-600"></div>
                </div>

                <!-- Tab Content -->
                <div v-else class="overflow-y-auto p-6 max-h-[60vh]">
                    <!-- ── Basic Info ── -->
                    <form v-if="activeTab === 'info'" class="space-y-4" @submit.prevent="handleSaveInfo">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Name
                                <span class="text-red-500">*</span>
                            </label>

                            <CInput
                                v-model="infoForm.name"
                                placeholder="Full name"
                                :class="{ 'border-red-500': infoErrors.name }"
                            />

                            <p v-if="infoErrors.name" class="mt-1 text-xs text-red-600">
                                {{ infoErrors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Email
                                <span class="text-red-500">*</span>
                            </label>

                            <CInput
                                v-model="infoForm.email"
                                type="email"
                                placeholder="user@example.com"
                                :class="{ 'border-red-500': infoErrors.email }"
                            />

                            <p v-if="infoErrors.email" class="mt-1 text-xs text-red-600">
                                {{ infoErrors.email }}
                            </p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <CButton type="submit" preset="primary" :disabled="modalLoading" icon="mdi:check">
                                {{ modalLoading ? 'Saving...' : 'Save Changes' }}
                            </CButton>
                        </div>
                    </form>

                    <!-- ── Role ── -->
                    <div v-else-if="activeTab === 'role'" class="space-y-5">
                        <p class="text-sm text-gray-500">
                            Select the role for this user. The role defines the base set of permissions.
                        </p>

                        <div class="space-y-2">
                            <label
                                v-for="role in options.roles"
                                :key="role"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition"
                                :class="[
                                    {
                                        'border-red-500 bg-red-50': roleForm === role,
                                        'border-gray-200 hover:border-gray-300 hover:bg-gray-50': roleForm !== role,
                                    },
                                ]"
                                @click="roleForm = role"
                            >
                                <!-- Radio indicator -->
                                <div
                                    :class="[
                                        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2',
                                        {
                                            'border-red-600 bg-red-600': roleForm === role,
                                            'border-gray-300': roleForm !== role,
                                        },
                                    ]"
                                >
                                    <div v-if="roleForm === role" class="h-2 w-2 rounded-full bg-white"></div>
                                </div>

                                <!-- Role badge -->
                                <span
                                    :class="[
                                        'inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        getRoleColor(role),
                                    ]"
                                >
                                    {{ getRoleLabel(role) }}
                                    <span v-if="isCurrentRole(role)" class="italic">(current role)</span>
                                </span>
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <CButton preset="primary" :disabled="modalLoading" icon="mdi:check" @click="handleSaveRole">
                                {{ modalLoading ? 'Saving...' : 'Save Role' }}
                            </CButton>
                        </div>
                    </div>

                    <!-- ── Client ── -->
                    <div v-else-if="activeTab === 'client'" class="space-y-5">
                        <!-- Current client info -->
                        <div v-if="selectedUserDetail?.user.customer_id">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Current client
                                </p>

                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            {{
                                                selectedUserDetail.user.client?.name ??
                                                'Client #' + selectedUserDetail.user.customer_id
                                            }}
                                        </p>

                                        <div class="mt-1 flex items-center gap-2">
                                            <span
                                                v-if="selectedUserDetail.user.client_role === 'admin'"
                                                class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700"
                                            >
                                                <Icon icon="mdi:crown" class="text-xs" />
                                                Admin
                                            </span>

                                            <span
                                                v-else
                                                class="inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600"
                                            >
                                                Member
                                            </span>
                                        </div>
                                    </div>

                                    <CButton
                                        v-if="selectedUserDetail.user.client_role !== 'admin'"
                                        preset="outlined-black"
                                        icon="mdi:crown-outline"
                                        class="shrink-0 text-xs"
                                        :disabled="editClientActionLoading"
                                        @click="handleSetAdminInEdit"
                                    >
                                        {{ editClientActionLoading ? 'Updating...' : 'Set as Admin' }}
                                    </CButton>
                                </div>
                            </div>
                        </div>

                        <!-- No client assigned -->
                        <div v-else>
                            <div
                                class="mb-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center"
                            >
                                <Icon icon="mdi:account-off-outline" class="mx-auto mb-1 text-2xl text-gray-400" />
                                <p class="text-sm text-gray-500">No client assigned to this user</p>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Assign to client</label>

                                    <div class="relative">
                                        <input
                                            v-model="editClientSearch"
                                            type="text"
                                            placeholder="Search client by name..."
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                            @input="onEditClientSearchInput"
                                        />

                                        <div
                                            v-if="editClientSearchLoading"
                                            class="absolute top-1/2 right-3 -translate-y-1/2"
                                        >
                                            <div
                                                class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-red-600"
                                            ></div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="editClientResults.length > 0"
                                        class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                                    >
                                        <button
                                            v-for="c in editClientResults"
                                            :key="c.id"
                                            type="button"
                                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-gray-50"
                                            @click="selectEditClient(c)"
                                        >
                                            <Icon icon="mdi:domain" class="shrink-0 text-gray-400" />
                                            <span class="font-medium text-gray-900">{{ c.name }}</span>
                                        </button>
                                    </div>

                                    <div
                                        v-if="editClientSelected"
                                        class="mt-2 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2"
                                    >
                                        <Icon icon="mdi:domain" class="shrink-0 text-red-600" />
                                        <span class="flex-1 text-sm font-medium text-gray-900">
                                            {{ editClientSelected.name }}
                                        </span>

                                        <button
                                            type="button"
                                            class="text-gray-400 hover:text-gray-600"
                                            @click="
                                                editClientSelected = null;
                                                editClientSearch = '';
                                            "
                                        >
                                            <Icon icon="mdi:close" class="text-sm" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Client role selector -->
                                <div v-if="editClientSelected">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Client Role</label>

                                    <div class="flex gap-3">
                                        <label
                                            v-for="roleOpt in ['admin', 'member'] as const"
                                            :key="roleOpt"
                                            class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border p-3 transition"
                                            :class="[
                                                {
                                                    'border-red-500 bg-red-50': editClientRole === roleOpt,
                                                    'border-gray-200 hover:border-gray-300 hover:bg-gray-50':
                                                        editClientRole !== roleOpt,
                                                },
                                            ]"
                                            @click="editClientRole = roleOpt"
                                        >
                                            <div
                                                :class="[
                                                    'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2',
                                                    {
                                                        'border-red-600 bg-red-600': editClientRole === roleOpt,
                                                        'border-gray-300': editClientRole !== roleOpt,
                                                    },
                                                ]"
                                            >
                                                <div
                                                    v-if="editClientRole === roleOpt"
                                                    class="h-1.5 w-1.5 rounded-full bg-white"
                                                ></div>
                                            </div>

                                            <div>
                                                <span class="flex items-center gap-1 text-sm font-medium text-gray-800">
                                                    <Icon
                                                        v-if="roleOpt === 'admin'"
                                                        icon="mdi:crown"
                                                        class="text-red-600"
                                                    />
                                                    {{ roleOpt === 'admin' ? 'Admin' : 'Member' }}
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div v-if="editClientSelected" class="flex justify-end pt-1">
                                    <CButton
                                        preset="primary"
                                        icon="mdi:account-arrow-right"
                                        :disabled="editClientActionLoading"
                                        @click="handleAssignClient"
                                    >
                                        {{ editClientActionLoading ? 'Assigning...' : 'Assign Client' }}
                                    </CButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Permissions ── -->
                    <div v-else-if="activeTab === 'permissions'" class="space-y-5">
                        <!-- Info banner -->
                        <div
                            class="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-700"
                        >
                            <Icon icon="mdi:information-outline" class="mt-0.5 shrink-0 text-base" />

                            <p>
                                These are
                                <strong>direct permissions</strong>
                                assigned to the user, independent of their role. Permissions already granted via role
                                are shown in blue and cannot be unchecked here.
                            </p>
                        </div>

                        <div v-if="selectedUserDetail?.role_permissions.length" class="text-xs text-gray-500">
                            <strong>{{ selectedUserDetail.role_permissions.length }}</strong>
                            permissions already granted via role.
                        </div>

                        <!-- Permission groups -->
                        <div
                            v-for="(groupPerms, groupName) in permissionGroups"
                            :key="groupName"
                            class="overflow-hidden rounded-lg border border-gray-200"
                        >
                            <!-- Group header -->
                            <button
                                type="button"
                                class="flex w-full items-center justify-between bg-gray-50 px-4 py-3 text-left transition hover:bg-gray-100"
                                @click="toggleGroup(groupPerms)"
                            >
                                <span class="text-sm font-semibold capitalize text-gray-800">
                                    {{ String(groupName).replace('_', ' ') }}
                                </span>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">
                                        {{ groupPerms.filter((p) => selectedPermissions.includes(p)).length }}/{{
                                            groupPerms.length
                                        }}
                                    </span>

                                    <div
                                        :class="[
                                            'flex h-5 w-5 items-center justify-center rounded border-2',
                                            {
                                                'border-red-600 bg-red-600': isGroupFullySelected(groupPerms),
                                                'border-red-300 bg-red-50': isGroupPartiallySelected(groupPerms),
                                                'border-gray-300':
                                                    !isGroupFullySelected(groupPerms) &&
                                                    !isGroupPartiallySelected(groupPerms),
                                            },
                                        ]"
                                    >
                                        <Icon
                                            v-if="isGroupFullySelected(groupPerms)"
                                            icon="mdi:check"
                                            class="text-xs text-white"
                                        />

                                        <Icon
                                            v-else-if="isGroupPartiallySelected(groupPerms)"
                                            icon="mdi:minus"
                                            class="text-xs text-red-500"
                                        />
                                    </div>
                                </div>
                            </button>

                            <!-- Permissions list -->
                            <div class="divide-y divide-gray-100">
                                <div
                                    v-for="perm in groupPerms"
                                    :key="perm"
                                    class="flex items-center gap-3 px-4 py-2.5"
                                    :class="[
                                        {
                                            'cursor-pointer hover:bg-gray-50':
                                                !selectedUserDetail?.role_permissions.includes(perm),
                                            'opacity-70': selectedUserDetail?.role_permissions.includes(perm),
                                        },
                                    ]"
                                    @click="
                                        !selectedUserDetail?.role_permissions.includes(perm) && togglePermission(perm)
                                    "
                                >
                                    <!-- Via role (read-only, blue) -->
                                    <div
                                        v-if="selectedUserDetail?.role_permissions.includes(perm)"
                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 border-blue-400 bg-blue-100"
                                    >
                                        <Icon icon="mdi:check" class="text-[10px] text-blue-600" />
                                    </div>

                                    <!-- Direct permission (toggleable, red) -->
                                    <div
                                        v-else
                                        :class="[
                                            'flex h-4 w-4 shrink-0 items-center justify-center rounded border-2',
                                            {
                                                'border-red-600 bg-red-600': selectedPermissions.includes(perm),
                                                'border-gray-300': !selectedPermissions.includes(perm),
                                            },
                                        ]"
                                    >
                                        <Icon
                                            v-if="selectedPermissions.includes(perm)"
                                            icon="mdi:check"
                                            class="text-[10px] text-white"
                                        />
                                    </div>

                                    <span
                                        :class="[
                                            'text-sm',
                                            {
                                                'text-gray-400': selectedUserDetail?.role_permissions.includes(perm),
                                                'text-gray-700': !selectedUserDetail?.role_permissions.includes(perm),
                                            },
                                        ]"
                                    >
                                        {{ perm }}
                                    </span>

                                    <span
                                        v-if="selectedUserDetail?.role_permissions.includes(perm)"
                                        class="ml-auto text-xs text-blue-500"
                                    >
                                        via role
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <p class="text-xs text-gray-500">
                                {{ selectedPermissions.length }} extra permission(s) selected
                            </p>

                            <CButton
                                preset="primary"
                                :disabled="modalLoading"
                                icon="mdi:check"
                                @click="handleSavePermissions"
                            >
                                {{ modalLoading ? 'Saving...' : 'Save Permissions' }}
                            </CButton>
                        </div>
                    </div>

                    <!-- ── Password ── -->
                    <form v-if="activeTab === 'password'" class="space-y-4" @submit.prevent="handleSavePassword">
                        <p class="text-sm text-gray-500">Update the password for this user.</p>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                New Password
                                <span class="text-red-500">*</span>
                            </label>

                            <CPasswodInput
                                v-model="passwordForm.password"
                                name="password"
                                type="password"
                                placeholder="••••••••"
                                :disabled="modalLoading"
                                :class="{ 'border-red-500': passwordErrors.password }"
                            />

                            <p v-if="passwordErrors.password" class="mt-1 text-xs text-red-600">
                                {{ passwordErrors.password }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Confirm Password
                                <span class="text-red-500">*</span>
                            </label>

                            <CPasswodInput
                                v-model="passwordForm.password_confirmation"
                                name="password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                :disabled="modalLoading"
                                :class="{ 'border-red-500': passwordErrors.password_confirmation }"
                            />

                            <p v-if="passwordErrors.password_confirmation" class="mt-1 text-xs text-red-600">
                                {{ passwordErrors.password_confirmation }}
                            </p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <CButton type="submit" preset="primary" :disabled="modalLoading" icon="mdi:check">
                                {{ modalLoading ? 'Saving...' : 'Save Password' }}
                            </CButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Create User Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="
                () => {
                    showCreateModal = false;
                    resetCreateForm();
                }
            "
        >
            <div class="flex w-full max-w-lg flex-col rounded-xl bg-white shadow-xl">
                <!-- Header -->
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-red-100">
                            <Icon icon="mdi:account-plus" class="text-lg text-red-700" />
                        </div>

                        <h2 class="text-base font-semibold text-gray-900">Create User</h2>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                        @click="
                            () => {
                                showCreateModal = false;
                                resetCreateForm();
                            }
                        "
                    >
                        <Icon icon="mdi:close" class="text-lg" />
                    </button>
                </div>

                <!-- Body -->
                <form class="overflow-y-auto p-6 max-h-[70vh]" @submit.prevent="handleCreateUser">
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Name
                                <span class="text-red-500">*</span>
                            </label>

                            <CInput
                                v-model="createForm.name"
                                placeholder="Full name"
                                :class="{ 'border-red-500': createErrors.name }"
                            />

                            <p v-if="createErrors.name" class="mt-1 text-xs text-red-600">
                                {{ createErrors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Email
                                <span class="text-red-500">*</span>
                            </label>

                            <CInput
                                v-model="createForm.email"
                                type="email"
                                placeholder="user@example.com"
                                :class="{ 'border-red-500': createErrors.email }"
                            />

                            <p v-if="createErrors.email" class="mt-1 text-xs text-red-600">
                                {{ createErrors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <CPasswodInput
                            v-model="createForm.password"
                            name="password"
                            type="password"
                            :class="{ 'border-red-500': createErrors.password }"
                            label="Password"
                            placeholder="••••••••"
                            :error="createErrors.password"
                            required
                        />

                        <!-- Confirm Password -->
                        <CPasswodInput
                            v-model="createForm.password_confirmation"
                            name="password_confirmation"
                            type="password"
                            :class="{ 'border-red-500': createErrors.password_confirmation }"
                            label="Confirm New Password"
                            placeholder="••••••••"
                            :error="createErrors.password_confirmation"
                            required
                        />

                        <!-- Role -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Role
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="space-y-2">
                                <label
                                    v-for="role in options.roles"
                                    :key="role"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition"
                                    :class="[
                                        {
                                            'border-red-500 bg-red-50': createForm.role === role,
                                            'border-gray-200 hover:border-gray-300 hover:bg-gray-50':
                                                createForm.role !== role,
                                        },
                                    ]"
                                    @click="createForm.role = role"
                                >
                                    <div
                                        :class="[
                                            'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2',
                                            {
                                                'border-red-600 bg-red-600': createForm.role === role,
                                                'border-gray-300': createForm.role !== role,
                                            },
                                        ]"
                                    >
                                        <div
                                            v-if="createForm.role === role"
                                            class="h-1.5 w-1.5 rounded-full bg-white"
                                        ></div>
                                    </div>

                                    <span
                                        :class="[
                                            'inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            getRoleColor(role),
                                        ]"
                                    >
                                        {{ getRoleLabel(role) }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Client (optional) -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Client
                            <span class="text-xs font-normal text-gray-400">(optional)</span>
                        </label>

                        <div class="relative">
                            <input
                                v-model="createClientSearch"
                                type="text"
                                placeholder="Search client by name..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                @input="onCreateClientSearchInput"
                            />

                            <div v-if="createClientSearchLoading" class="absolute top-1/2 right-3 -translate-y-1/2">
                                <div
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-red-600"
                                ></div>
                            </div>
                        </div>

                        <div
                            v-if="createClientResults.length > 0"
                            class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                        >
                            <button
                                v-for="c in createClientResults"
                                :key="c.id"
                                type="button"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-gray-50"
                                @click="selectCreateClient(c)"
                            >
                                <Icon icon="mdi:domain" class="shrink-0 text-gray-400" />
                                <span class="font-medium text-gray-900">{{ c.name }}</span>
                            </button>
                        </div>

                        <div
                            v-if="createClientSelected"
                            class="mt-2 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2"
                        >
                            <Icon icon="mdi:domain" class="shrink-0 text-red-600" />
                            <span class="flex-1 text-sm font-medium text-gray-900">
                                {{ createClientSelected.name }}
                            </span>

                            <button type="button" class="text-gray-400 hover:text-gray-600" @click="clearCreateClient">
                                <Icon icon="mdi:close" class="text-sm" />
                            </button>
                        </div>
                    </div>

                    <!-- Client Role (visible only when a client is selected) -->
                    <div v-if="createClientSelected">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Client Role</label>

                        <div class="flex gap-3">
                            <label
                                v-for="roleOpt in ['admin', 'member'] as const"
                                :key="roleOpt"
                                class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border p-3 transition"
                                :class="[
                                    {
                                        'border-red-500 bg-red-50': createForm.client_role === roleOpt,
                                        'border-gray-200 hover:border-gray-300 hover:bg-gray-50':
                                            createForm.client_role !== roleOpt,
                                    },
                                ]"
                                @click="createForm.client_role = roleOpt"
                            >
                                <div
                                    :class="[
                                        'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2',
                                        {
                                            'border-red-600 bg-red-600': createForm.client_role === roleOpt,
                                            'border-gray-300': createForm.client_role !== roleOpt,
                                        },
                                    ]"
                                >
                                    <div
                                        v-if="createForm.client_role === roleOpt"
                                        class="h-1.5 w-1.5 rounded-full bg-white"
                                    ></div>
                                </div>

                                <span class="flex items-center gap-1 text-sm font-medium text-gray-800">
                                    <Icon v-if="roleOpt === 'admin'" icon="mdi:crown" class="text-red-600" />
                                    {{ roleOpt === 'admin' ? 'Admin' : 'Member' }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 flex justify-end gap-2">
                        <CButton
                            type="button"
                            preset="outlined-black"
                            @click="
                                () => {
                                    showCreateModal = false;
                                    resetCreateForm();
                                }
                            "
                        >
                            Cancel
                        </CButton>

                        <CButton type="submit" preset="primary" :disabled="createLoading" icon="mdi:account-plus">
                            {{ createLoading ? 'Creating...' : 'Create User' }}
                        </CButton>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
