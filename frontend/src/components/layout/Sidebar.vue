<template>
    <aside
        class="sticky top-0 hidden h-screen shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-slate-50 transition-[width] duration-200 ease-in-out lg:flex"
        :style="{ width: collapsed ? '4.5rem' : '17rem' }"
    >
        <!-- Brand -->
        <div
            class="flex shrink-0 items-center gap-3 border-b border-slate-200 py-4"
            :class="collapsed ? 'justify-center px-2' : 'px-4'"
        >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center" aria-hidden="true">
                <svg viewBox="0 0 40 40" class="h-10 w-10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="ams-hex" x1="8" y1="4" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#60A5FA" />
                            <stop offset="1" stop-color="#2563EB" />
                        </linearGradient>
                    </defs>
                    <path d="M20 2.5L34.5 11V29L20 37.5L5.5 29V11L20 2.5Z" fill="url(#ams-hex)" />
                    <path d="M20 8L29.5 13.5V24.5L20 30L10.5 24.5V13.5L20 8Z" fill="#EFF6FF" fill-opacity="0.95" />
                    <path d="M20 12L25.5 15.2V21.8L20 25L14.5 21.8V15.2L20 12Z" fill="#3B82F6" />
                </svg>
            </div>
            <div v-if="!collapsed" class="min-w-0 overflow-hidden">
                <p class="truncate text-base font-bold leading-tight text-slate-900">AMS</p>
                <p class="truncate text-[11px] leading-snug text-slate-400">
                    Application Management System
                </p>
            </div>
        </div>

        <!-- Navigation -->
        <nav
            class="scrollbar-light min-h-0 flex-1 overflow-y-auto overflow-x-hidden py-4"
            :class="collapsed ? 'px-2' : 'px-3'"
        >
            <div
                v-for="(section, sectionIndex) in navigationSections"
                :key="section.title"
                :class="sectionIndex > 0 ? 'mt-5' : ''"
            >
                <p
                    v-if="!collapsed"
                    class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                >
                    {{ section.title }}
                </p>
                <div
                    v-else-if="sectionIndex > 0"
                    class="mx-auto mb-2 h-px w-6 bg-slate-200"
                />

                <div class="space-y-0.5">
                    <RouterLink
                        v-for="item in section.items"
                        :key="item.name"
                        :to="item.to"
                        :title="collapsed ? item.label : undefined"
                        class="group flex items-center gap-3 rounded-lg py-2 text-sm font-medium transition"
                        :class="[
                            collapsed ? 'justify-center px-2' : 'px-3',
                            isActive(item)
                                ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/25'
                                : 'text-slate-700 hover:bg-slate-100',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5 shrink-0"
                            :class="isActive(item) ? 'text-white' : 'text-slate-400 group-hover:text-slate-500'"
                        />
                        <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                    </RouterLink>
                </div>
            </div>
        </nav>

        <!-- Collapse -->
        <div class="shrink-0 border-t border-slate-200 p-3">
            <button
                type="button"
                class="flex w-full cursor-pointer items-center gap-2 rounded-lg py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                :class="collapsed ? 'justify-center px-2' : 'px-3'"
                :aria-expanded="!collapsed"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :title="collapsed ? 'Expand' : 'Collapse'"
                @click="collapsed = !collapsed"
            >
                <ChevronDoubleLeftIcon
                    class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200"
                    :class="collapsed ? 'rotate-180' : ''"
                />
                <span v-if="!collapsed">Collapse</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
    BellAlertIcon,
    BoltIcon,
    BuildingOffice2Icon,
    ChartBarIcon,
    ChevronDoubleLeftIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    Cog6ToothIcon,
    DevicePhoneMobileIcon,
    DocumentTextIcon,
    HomeIcon,
    LifebuoyIcon,
    PuzzlePieceIcon,
    PresentationChartLineIcon,
    QueueListIcon,
    ScaleIcon,
    ShieldCheckIcon,
    SparklesIcon,
    Squares2X2Icon,
    UserCircleIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

const collapsed = ref(false);
const route = useRoute();

const navigationSections = [
    {
        title: 'Overview',
        items: [
            { name: 'dashboard', label: 'Dashboard', to: { name: 'dashboard' }, icon: HomeIcon, match: ['dashboard'], exact: true },
            { name: 'profile', label: 'My Profile', to: { name: 'profile' }, icon: UserCircleIcon, match: ['profile', 'change-password'] },
        ],
    },
    {
        title: 'User & Access',
        items: [
            { name: 'users.index', label: 'Users', to: { name: 'users.index' }, icon: UsersIcon, match: ['users.'] },
            { name: 'roles.index', label: 'Roles & Permissions', to: { name: 'roles.index' }, icon: ShieldCheckIcon, match: ['roles.'] },
            { name: 'companies.index', label: 'Companies', to: { name: 'companies.index' }, icon: BuildingOffice2Icon, match: ['companies.'] },
        ],
    },
    {
        title: 'Business',
        items: [
            { name: 'customers.index', label: 'Customers', to: { name: 'customers.index' }, icon: UserGroupIcon, match: ['customers.'] },
            { name: 'applications.index', label: 'Applications', to: { name: 'applications.index' }, icon: DevicePhoneMobileIcon, match: ['applications.'] },
            { name: 'integrations.index', label: 'Integrations', to: { name: 'integrations.index' }, icon: PuzzlePieceIcon, match: ['integrations.'] },
            {
                name: 'content.dashboard',
                label: 'Content',
                to: { name: 'content.dashboard' },
                icon: DocumentTextIcon,
                match: ['content.'],
                exclude: ['content.workflow'],
            },
        ],
    },
    {
        title: 'Automation & Communication',
        items: [
            { name: 'notifications.dashboard', label: 'Notifications', to: { name: 'notifications.dashboard' }, icon: BellAlertIcon, match: ['notifications.'] },
            { name: 'automation.dashboard', label: 'Automation', to: { name: 'automation.dashboard' }, icon: BoltIcon, match: ['automation.'] },
            { name: 'workflows.dashboard', label: 'Workflows', to: { name: 'workflows.dashboard' }, icon: Squares2X2Icon, match: ['workflows.'] },
            { name: 'scheduler.dashboard', label: 'Scheduler', to: { name: 'scheduler.dashboard' }, icon: ClockIcon, match: ['scheduler.'] },
            { name: 'ai.dashboard', label: 'AI Assistant', to: { name: 'ai.dashboard' }, icon: SparklesIcon, match: ['ai.'] },
            { name: 'webhooks.index', label: 'Webhooks', to: { name: 'webhooks.index' }, icon: PuzzlePieceIcon, match: ['webhooks.'] },
            { name: 'content.workflow', label: 'Content Approvals', to: { name: 'content.workflow' }, icon: DocumentTextIcon, match: ['content.workflow'] },
            { name: 'queue.dashboard', label: 'Queue', to: { name: 'queue.dashboard' }, icon: QueueListIcon, match: ['queue.'] },
            { name: 'sync.dashboard', label: 'Sync', to: { name: 'sync.dashboard' }, icon: BoltIcon, match: ['sync.', 'mappings.'] },
        ],
    },
    {
        title: 'Support & Governance',
        items: [
            { name: 'support.dashboard', label: 'Support', to: { name: 'support.dashboard' }, icon: LifebuoyIcon, match: ['support.'] },
            { name: 'analytics.dashboard', label: 'Analytics', to: { name: 'analytics.dashboard' }, icon: ChartBarIcon, match: ['analytics.'] },
            { name: 'compliance.dashboard', label: 'Compliance', to: { name: 'compliance.dashboard' }, icon: ScaleIcon, match: ['compliance.'] },
            { name: 'audit.activity', label: 'Audit Logs', to: { name: 'audit.activity' }, icon: ClipboardDocumentListIcon, match: ['audit.'] },
        ],
    },
    {
        title: 'System',
        items: [
            { name: 'settings.general', label: 'Settings', to: { name: 'settings.general' }, icon: Cog6ToothIcon, match: ['settings.'] },
            { name: 'monitoring.dashboard', label: 'Monitoring', to: { name: 'monitoring.dashboard' }, icon: PresentationChartLineIcon, match: ['monitoring.'] },
        ],
    },
];

function matchesPattern(name, pattern) {
    if (pattern.endsWith('.')) {
        return name.startsWith(pattern) || name === pattern.slice(0, -1);
    }

    return name === pattern || name.startsWith(`${pattern}.`) || name.startsWith(pattern);
}

function isActive(item) {
    const name = route.name;
    if (!name || typeof name !== 'string') {
        return false;
    }

    if (item.exclude?.some((pattern) => matchesPattern(name, pattern))) {
        return false;
    }

    if (item.exact) {
        return name === item.name;
    }

    return item.match.some((pattern) => matchesPattern(name, pattern));
}
</script>
