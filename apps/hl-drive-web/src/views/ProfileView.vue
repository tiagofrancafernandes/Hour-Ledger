<script setup lang="ts">
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useAuth } from '@/composables/useAuth';
import { useChangePassword } from '@/composables/useChangePassword';

const { user, role, permissions } = useAuth();
const {
    currentPassword,
    password,
    passwordConfirmation,
    isLoading: isChangingPassword,
    error: changePasswordError,
    success: changePasswordSuccess,
    changePassword,
    reset: resetChangePassword,
} = useChangePassword();

// Tabs
type Tab = 'overview' | 'permissions' | 'password';
const activeTab = ref<Tab>('overview');

// Edit mode
const isEditing = ref(false);
const editName = ref('');

function startEdit(): void {
    editName.value = user.value?.name ?? '';
    isEditing.value = true;
}

function cancelEdit(): void {
    isEditing.value = false;
    editName.value = '';
}

// User initial for avatar
const userInitial = computed(() => {
    return user.value?.name?.charAt(0)?.toUpperCase() ?? '?';
});

// Role badge color
const roleBadgeClass = computed(() => {
    const map: Record<string, string> = {
        super_admin: 'bg-purple-100 text-purple-700 border-purple-200',
        admin: 'bg-red-100 text-red-700 border-red-200',
        manager: 'bg-blue-100 text-blue-700 border-blue-200',
        customer: 'bg-green-100 text-green-700 border-green-200',
    };

    return map[role.value ?? ''] ?? 'bg-gray-100 text-gray-700 border-gray-200';
});

const roleLabel = computed(() => {
    const map: Record<string, string> = {
        super_admin: 'Super Admin',
        admin: 'Admin',
        manager: 'Manager',
        customer: 'Customer',
    };

    return map[role.value ?? ''] ?? role.value ?? 'No role';
});

// Permissions grouped by resource
const groupedPermissions = computed(() => {
    return permissions.value.reduce((groups: Record<string, string[]>, permission: string) => {
        const [group] = permission.split('.', 1);

        if (!group) {
            return groups;
        }

        groups[group] ??= [];

        groups[group].push(permission);

        return groups;
    }, {});
});

const permissionGroupCount = computed(() => Object.keys(groupedPermissions.value).length);
const totalPermissions = computed(() => permissions.value.length);

// Permission action label
function permissionAction(perm: string): string {
    const action = perm.split('.')[1] ?? perm;

    const labels: Record<string, string> = {
        view: 'View',
        view_any: 'View Any',
        create: 'Create',
        update: 'Update',
        delete: 'Delete',
        approve: 'Approve',
        credit: 'Credit',
        debit: 'Debit',
        adjust: 'Adjust',
    };

    return labels[action] ?? action;
}

// Permission action color
function permissionColor(perm: string): string {
    const action = perm.split('.')[1] ?? '';

    const colors: Record<string, string> = {
        view: 'bg-blue-50 text-blue-700 border-blue-200',
        view_any: 'bg-blue-50 text-blue-700 border-blue-200',
        create: 'bg-green-50 text-green-700 border-green-200',
        update: 'bg-amber-50 text-amber-700 border-amber-200',
        delete: 'bg-red-50 text-red-700 border-red-200',
        approve: 'bg-purple-50 text-purple-700 border-purple-200',
        credit: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        debit: 'bg-orange-50 text-orange-700 border-orange-200',
        adjust: 'bg-sky-50 text-sky-700 border-sky-200',
    };

    return colors[action] ?? 'bg-gray-50 text-gray-700 border-gray-200';
}

// Resource icon
function resourceIcon(group: string): string {
    const icons: Record<string, string> = {
        client: 'heroicons:users',
        wallet: 'heroicons:wallet',
        ledger: 'heroicons:book-open',
        tag: 'heroicons:tag',
        report: 'heroicons:chart-bar',
        timer: 'heroicons:clock',
        import: 'heroicons:arrow-up-tray',
        credit_purchase: 'heroicons:credit-card',
    };

    return icons[group] ?? 'heroicons:shield-check';
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <!-- Profile Header Card -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
            <!-- Top banner -->
            <div class="h-8 bg-linear-to-r from-gray-900 to-gray-700 relative">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-4 left-8 w-16 h-16 rounded-full border-2 border-white/30"></div>
                    <div class="absolute top-2 right-16 w-8 h-8 rounded-full border-2 border-white/20"></div>
                    <div class="absolute bottom-2 left-24 w-10 h-10 rounded-full border border-white/20"></div>
                </div>
            </div>

            <!-- Avatar + info row -->
            <div class="px-6 pb-6 pt-14">
                <div class="flex items-end justify-between -mt-10 mb-4">
                    <!-- Avatar -->
                    <div
                        class="w-20 h-20 rounded-2xl bg-red-600 border-4 border-white shadow-md flex items-center justify-center text-white text-3xl font-bold select-none flex-shrink-0"
                    >
                        {{ userInitial }}
                    </div>

                    <!-- Edit button -->
                    <div class="flex items-center gap-2 pb-1">
                        <button
                            v-if="!isEditing && activeTab === 'overview'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                            @click="startEdit"
                        >
                            <Icon icon="heroicons:pencil-square" class="w-4 h-4" />
                            Edit Profile
                        </button>
                    </div>
                </div>

                <!-- Name + role -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <h1 class="text-xl font-bold text-gray-900">{{ user?.name }}</h1>

                    <span
                        :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                            roleBadgeClass,
                        ]"
                    >
                        {{ roleLabel }}
                    </span>
                </div>

                <p class="text-sm text-gray-500 mt-0.5">{{ user?.email }}</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 mb-6 bg-gray-100 rounded-xl p-1 w-fit">
            <button
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                    {
                        'bg-white text-gray-900 shadow-sm': activeTab === 'overview',
                        'text-gray-500 hover:text-gray-800': activeTab !== 'overview',
                    },
                ]"
                @click="activeTab = 'overview'"
            >
                <Icon icon="heroicons:user-circle" class="w-4 h-4" />
                Overview
            </button>

            <button
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                    {
                        'bg-white text-gray-900 shadow-sm': activeTab === 'permissions',
                        'text-gray-500 hover:text-gray-800': activeTab !== 'permissions',
                    },
                ]"
                @click="activeTab = 'permissions'"
            >
                <Icon icon="heroicons:shield-check" class="w-4 h-4" />
                Permissions
                <span
                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-200 text-gray-600 text-xs font-bold"
                >
                    {{ totalPermissions }}
                </span>
            </button>

            <button
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                    {
                        'bg-white text-gray-900 shadow-sm': activeTab === 'password',
                        'text-gray-500 hover:text-gray-800': activeTab !== 'password',
                    },
                ]"
                @click="activeTab = 'password'"
            >
                <Icon icon="heroicons:lock-closed" class="w-4 h-4" />
                Password
            </button>
        </div>

        <!-- Tab: Overview -->
        <div v-if="activeTab === 'overview'">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main info card -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">
                        Account Information
                    </h2>

                    <!-- View mode -->
                    <div v-if="!isEditing" class="space-y-5">
                        <div class="flex items-start gap-4 pb-5 border-b border-gray-100">
                            <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <Icon icon="heroicons:user" class="w-4 h-4 text-gray-500" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Full Name</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">{{ user?.name }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 pb-5 border-b border-gray-100">
                            <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <Icon icon="heroicons:envelope" class="w-4 h-4 text-gray-500" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Email Address</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">{{ user?.email }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <Icon icon="heroicons:identification" class="w-4 h-4 text-gray-500" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Role</p>
                                <span
                                    :class="[
                                        'mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                                        roleBadgeClass,
                                    ]"
                                >
                                    {{ roleLabel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Edit mode -->
                    <div v-else class="space-y-5">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                Full Name
                            </label>
                            <input
                                v-model="editName"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-shadow"
                                placeholder="Your name"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                Email Address
                            </label>
                            <input
                                type="email"
                                :value="user?.email"
                                disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-400 cursor-not-allowed"
                            />
                            <p class="mt-1 text-xs text-gray-400">Email cannot be changed</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <CButton preset="red-md" @click="cancelEdit">Save Changes</CButton>
                            <CButton preset="lightgray-md" @click="cancelEdit">Cancel</CButton>
                        </div>
                    </div>
                </div>

                <!-- Side stats card -->
                <div class="space-y-4">
                    <!-- Access summary -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Access Summary</h3>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Icon icon="heroicons:shield-check" class="w-4 h-4 text-gray-400" />
                                    <span class="text-sm text-gray-600">Permissions</span>
                                </div>
                                <span class="text-sm font-bold text-gray-900">{{ totalPermissions }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Icon icon="heroicons:rectangle-group" class="w-4 h-4 text-gray-400" />
                                    <span class="text-sm text-gray-600">Modules</span>
                                </div>
                                <span class="text-sm font-bold text-gray-900">{{ permissionGroupCount }}</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <button
                                class="w-full text-xs text-red-600 font-medium hover:text-red-700 transition-colors text-left"
                                @click="activeTab = 'permissions'"
                            >
                                View all permissions →
                            </button>
                        </div>
                    </div>

                    <!-- Role info card -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Current Role</h3>

                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center flex-shrink-0"
                            >
                                <Icon icon="heroicons:identification" class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ roleLabel }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">System role</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Password -->
        <div v-if="activeTab === 'password'">
            <div class="max-w-2xl">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-6">Change Password</h2>

                    <!-- Success Message -->
                    <div v-if="changePasswordSuccess" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-medium text-green-800">Password has been changed successfully</p>
                    </div>

                    <!-- Error Message -->
                    <div v-if="changePasswordError" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm font-medium text-red-800">{{ changePasswordError }}</p>
                    </div>

                    <!-- Form -->
                    <div class="space-y-5">
                        <CPasswodInput
                            v-model="currentPassword"
                            name="current_password"
                            type="password"
                            label="Current Password"
                            placeholder="••••••••"
                            :disabled="isChangingPassword"
                        />

                        <CPasswodInput
                            v-model="password"
                            name="password"
                            type="password"
                            label="New Password"
                            placeholder="••••••••"
                            :disabled="isChangingPassword"
                        />

                        <CPasswodInput
                            v-model="passwordConfirmation"
                            name="password_confirmation"
                            type="password"
                            label="Confirm New Password"
                            placeholder="••••••••"
                            :disabled="isChangingPassword"
                        />

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <CButton
                                :disabled="
                                    isChangingPassword ||
                                    !currentPassword ||
                                    String(password || '').length < 6 ||
                                    String(passwordConfirmation || '').length < 6 ||
                                    password !== passwordConfirmation
                                "
                                @click="changePassword"
                            >
                                <span v-if="!isChangingPassword">Change Password</span>
                                <span v-else>Changing...</span>
                            </CButton>
                            <CButton preset="outlined-black" @click="resetChangePassword">Clear</CButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Permissions -->
        <div v-if="activeTab === 'permissions'">
            <!-- Empty state -->
            <div
                v-if="!totalPermissions"
                class="bg-white rounded-2xl border border-gray-200 shadow-sm p-12 text-center"
            >
                <Icon icon="heroicons:shield-exclamation" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p class="text-sm font-medium text-gray-500">No permissions assigned</p>
                <p class="text-xs text-gray-400 mt-1">Contact an administrator to request access</p>
            </div>

            <!-- Permission groups -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div
                    v-for="(perms, group) in groupedPermissions"
                    :key="group"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5"
                >
                    <!-- Group header -->
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-gray-900 flex items-center justify-center flex-shrink-0">
                            <Icon :icon="resourceIcon(group)" class="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 capitalize">
                                {{ group.replace('_', ' ') }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ perms.length }} permission{{ perms.length !== 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>

                    <!-- Permission pills -->
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="perm in perms"
                            :key="perm"
                            :class="[
                                'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium border',
                                permissionColor(perm),
                            ]"
                        >
                            {{ permissionAction(perm) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
