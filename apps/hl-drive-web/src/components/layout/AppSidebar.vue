<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { RouterLink, useRoute } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps<{
    collapsed: boolean;
}>();

const emit = defineEmits<{
    'toggle-collapse': [];
}>();

const route = useRoute();
const permissions = usePermissions();

const expandedGroups = ref<Set<string>>(new Set());

interface NavChild {
    label: string;
    to: string;
    icon: string;
}

interface NavItem {
    key: string;
    label: string;
    icon: string;
    to?: string;
    basePath?: string;
    show: boolean;
    children?: NavChild[];
}

const navItems = computed((): NavItem[] => {
    return [
        {
            key: 'clients',
            label: 'Clients',
            icon: 'heroicons:users',
            to: '/clients',
            show: permissions.canViewClients.value,
        },
        {
            key: 'invoices',
            label: 'Invoices',
            icon: 'heroicons:document-text',
            basePath: '/invoices',
            show: permissions.hasPermission('invoice.view') || permissions.hasPermission('invoice.view_any'),
            children: [
                {
                    label: 'All Invoices',
                    to: '/invoices',
                    icon: 'heroicons:document-text',
                },
                {
                    label: 'Products & Services',
                    to: '/products-services',
                    icon: 'heroicons:cube',
                },
            ],
        },
        {
            key: 'reports',
            label: 'Reports',
            icon: 'heroicons:chart-bar',
            to: '/reports',
            show: permissions.canViewReports.value,
        },
        {
            key: 'timers',
            label: 'Timers',
            icon: 'heroicons:clock',
            to: '/timers',
            show: permissions.hasPermission('timer.view') || permissions.hasPermission('timer.view_any'),
        },
        {
            key: 'tags',
            label: 'Tags',
            icon: 'heroicons:tag',
            to: '/tags',
            show: permissions.canViewTags.value,
        },
        {
            key: 'imports',
            label: 'Imports',
            icon: 'heroicons:arrow-up-tray',
            basePath: '/imports',
            show: permissions.hasPermission('import.view') || permissions.hasPermission('import.view_any'),
            children: [
                {
                    label: 'Import Plans',
                    to: '/imports',
                    icon: 'heroicons:document-text',
                },
                {
                    label: 'Upload',
                    to: '/imports/upload',
                    icon: 'heroicons:cloud-arrow-up',
                },
            ],
        },
        {
            key: 'payments',
            label: 'Payments',
            icon: 'heroicons:credit-card',
            basePath: '/payments',
            show: permissions.canApprovePayments.value,
            children: [
                {
                    label: 'Payment history',
                    to: '/payments/history',
                    icon: 'heroicons:credit-card',
                },
                {
                    label: 'Approvals',
                    to: '/payments/approvals',
                    icon: 'heroicons:check-badge',
                },
            ],
        },
        {
            key: 'admin',
            label: 'Admin',
            icon: 'heroicons:cog-6-tooth',
            basePath: '/admin',
            show: permissions.hasPermission('user.view_any'),
            children: [
                {
                    label: 'Users',
                    to: '/admin/users',
                    icon: 'heroicons:users',
                },
            ],
        },
    ].filter((item) => item.show);
});

function isDirectActive(to: string): boolean {
    return route.path === to || route.path.startsWith(to + '/');
}

function isChildActive(to: string): boolean {
    return route.path === to;
}

function isGroupActive(item: NavItem): boolean {
    if (item.basePath) {
        return route.path === item.basePath || route.path.startsWith(item.basePath + '/');
    }

    return false;
}

function isExpanded(key: string): boolean {
    if (props.collapsed) {
        return false;
    }

    return expandedGroups.value.has(key);
}

function toggleExpanded(key: string): void {
    if (expandedGroups.value.has(key)) {
        expandedGroups.value.delete(key);
    } else {
        expandedGroups.value.add(key);
    }
}

function syncExpandedGroups(): void {
    navItems.value.forEach((item) => {
        if (item.children && isGroupActive(item)) {
            expandedGroups.value.add(item.key);
        }
    });
}

watch(() => route.path, syncExpandedGroups, { immediate: true });
</script>

<template>
    <aside
        :class="[
            'flex flex-col h-full transition-all duration-300 ease-in-out flex-shrink-0 select-none',
            // 'bg-white dark:bg-gray-800/30 text-gray-700 dark:text-white', // bg muda com o schema
            'bg-gray-900 text-white', // bg muda com o schema
            { 'w-64': !collapsed, 'w-16': collapsed },
        ]"
    >
        <!-- Logo -->
        <div
            :class="[
                'flex items-center h-12 px-4 border-b border-white/10 flex-shrink-0 gap-3',
                { 'justify-center': collapsed },
            ]"
        >
            <RouterLink to="/" class="flex items-center gap-3 min-w-0">
                <div class="flex-shrink-0 w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center shadow-sm">
                    <Icon icon="heroicons:clock" class="w-4 h-4 text-white" />
                </div>

                <span v-if="!collapsed" class="text-sm font-bold text-gray-200 dark:text-white truncate tracking-tight">
                    Hours Ledger
                </span>
            </RouterLink>
        </div>

        <!-- Navigation Items (scrollable) -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 space-y-0.5 px-2">
            <template v-for="item in navItems" :key="item.key">
                <!-- Direct link (no children) -->
                <RouterLink
                    v-if="!item.children && item.to"
                    :to="item.to"
                    :title="collapsed ? item.label : undefined"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150',
                        {
                            'bg-red-600 dark:text-white shadow-sm': isDirectActive(item.to),
                            'text-gray-100 dark:hover:text-white hover:bg-gray-600/40 dark:hover:bg-white/10':
                                !isDirectActive(item.to),
                            'justify-center': collapsed,
                        },
                    ]"
                >
                    <Icon :icon="item.icon" class="w-5 h-5 flex-shrink-0" />
                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                </RouterLink>

                <!-- Group with children -->
                <div v-else-if="item.children">
                    <!-- Group header button -->
                    <button
                        :title="collapsed ? item.label : undefined"
                        :class="[
                            'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150',
                            {
                                // dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white
                                'dark:bg-white/10 dark:text-white': isGroupActive(item),
                                'dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white': !isGroupActive(item),
                                'justify-center': collapsed,
                            },
                        ]"
                        @click="!collapsed && toggleExpanded(item.key)"
                    >
                        <Icon :icon="item.icon" class="w-5 h-5 flex-shrink-0" />

                        <span v-if="!collapsed" class="flex-1 text-left truncate">
                            {{ item.label }}
                        </span>

                        <Icon
                            v-if="!collapsed"
                            icon="heroicons:chevron-down"
                            :class="[
                                'w-4 h-4 flex-shrink-0 transition-transform duration-200',
                                { 'rotate-180': isExpanded(item.key) },
                            ]"
                        />
                    </button>

                    <!-- Sub-items -->
                    <div
                        v-if="!collapsed && isExpanded(item.key)"
                        class="mt-0.5 ml-3 pl-3 border-l border-white/10 space-y-0.5"
                    >
                        <RouterLink
                            v-for="child in item.children"
                            :key="child.to"
                            :to="child.to"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors duration-150',
                                {
                                    'text-red-400 font-medium bg-red-400/10 dark:bg-red-500/10': isChildActive(
                                        child.to
                                    ),
                                    'text-gray-100 dark:hover:text-white hover:bg-gray-600/40 dark:hover:bg-white/10':
                                        !isChildActive(child.to),
                                },
                            ]"
                        >
                            <Icon :icon="child.icon" class="w-4 h-4 flex-shrink-0" />
                            <span class="truncate">{{ child.label }}</span>
                        </RouterLink>
                    </div>
                </div>
            </template>
        </nav>

        <!-- Collapse Toggle -->
        <div class="flex-shrink-0 border-t border-white/10 p-2">
            <button
                :class="[
                    'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:text-white hover:bg-white/10 transition-colors duration-150',
                    { 'justify-center': collapsed },
                ]"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                @click="emit('toggle-collapse')"
            >
                <Icon
                    :icon="collapsed ? 'heroicons:chevron-double-right' : 'heroicons:chevron-double-left'"
                    class="w-5 h-5 flex-shrink-0"
                />
                <span v-if="!collapsed" class="text-xs font-medium">Collapse</span>
            </button>
        </div>
    </aside>
</template>
