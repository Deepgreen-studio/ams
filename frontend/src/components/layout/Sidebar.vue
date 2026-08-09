<template>
    <!-- Mobile overlay -->
    <div
        v-if="appStore.sidebarOpen"
        class="fixed inset-0 z-40 bg-black/40 lg:hidden"
        aria-hidden="true"
        @click="appStore.closeSidebar()"
    />

    <aside
        class="fixed inset-y-0 left-0 z-50 flex h-screen shrink-0 flex-col overflow-visible bg-shell transition-[width,transform] duration-200 ease-in-out lg:sticky lg:top-0 lg:translate-x-0"
        :class="[
            appStore.sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
        :style="{ width: collapsed ? '5rem' : '16.5rem' }"
    >
        <!-- Brand -->
        <div
            class="relative z-20 flex shrink-0 items-center gap-3 overflow-visible py-5"
            :class="collapsed ? 'justify-center px-2' : 'px-5'"
        >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center" aria-hidden="true">
                <svg viewBox="0 0 40 40" class="h-9 w-9" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="20" cy="20" r="18" fill="#FF5C00" />
                    <path
                        d="M20 8.5L28.5 13.5V22.5L20 27.5L11.5 22.5V13.5L20 8.5Z"
                        stroke="white"
                        stroke-width="2"
                        fill="none"
                    />
                    <circle cx="20" cy="18" r="3.5" fill="white" />
                </svg>
            </div>
            <p v-if="!collapsed" class="truncate text-xl font-bold tracking-tight text-white">
                {{ appStore.appName }}
            </p>

            <!-- Collapse edge control (desktop) — white pill so it stays visible on the light canvas -->
            <button
                type="button"
                class="absolute -right-3.5 top-1/2 z-30 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-800 shadow-md ring-1 ring-black/5 hover:bg-zinc-50 lg:inline-flex"
                :aria-expanded="!collapsed"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :title="collapsed ? 'Expand' : 'Collapse'"
                @click="appStore.toggleSidebarCollapse()"
            >
                <ChevronLeftIcon
                    class="h-4 w-4 transition-transform duration-200"
                    :class="collapsed ? 'rotate-180' : ''"
                />
            </button>
        </div>

        <!-- Primary CTA -->
        <div class="shrink-0 px-4 pb-4" :class="collapsed ? 'px-2' : 'px-4'">
            <RouterLink
                :to="{ name: 'applications.create' }"
                class="flex items-center rounded-full bg-white text-sm font-semibold text-zinc-900 shadow-sm transition hover:bg-zinc-50"
                :class="collapsed ? 'justify-center p-2' : 'gap-3 py-2.5 pl-2.5 pr-4'"
                :title="collapsed ? 'Create application' : undefined"
                @click="closeMobile"
            >
                <span
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white"
                >
                    <PlusIcon class="h-4 w-4" />
                </span>
                <span v-if="!collapsed">Create application</span>
            </RouterLink>
        </div>

        <!-- Navigation -->
        <nav
            class="scrollbar-dark min-h-0 flex-1 overflow-y-auto overflow-x-hidden py-2"
            :class="collapsed ? 'px-2' : 'px-3'"
        >
            <div
                v-for="(section, sectionIndex) in navigationSections"
                :key="section.title"
                :class="sectionIndex > 0 ? 'mt-5' : ''"
            >
                <p
                    v-if="!collapsed"
                    class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-500"
                >
                    {{ section.title }}
                </p>
                <div
                    v-else-if="sectionIndex > 0"
                    class="mx-auto mb-2 h-px w-6 bg-zinc-700"
                />

                <div class="space-y-1">
                    <RouterLink
                        v-for="item in section.items"
                        :key="item.name"
                        :to="item.to"
                        :title="collapsed ? item.label : undefined"
                        class="group flex items-center gap-3 rounded-full py-2.5 text-sm font-medium transition"
                        :class="[
                            collapsed ? 'justify-center px-2' : 'px-3',
                            isActive(item)
                                ? 'bg-white text-brand-500 shadow-sm'
                                : 'text-zinc-300 hover:bg-white/5 hover:text-white',
                        ]"
                        @click="closeMobile"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5 shrink-0"
                            :class="isActive(item) ? 'text-brand-500' : 'text-zinc-400 group-hover:text-zinc-200'"
                        />
                        <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                    </RouterLink>
                </div>
            </div>
        </nav>

        <!-- Help -->
        <div class="shrink-0 p-4" :class="collapsed ? 'flex justify-center' : ''">
            <RouterLink
                :to="{ name: 'support.dashboard' }"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm transition hover:bg-brand-600"
                title="Help & Support"
                @click="closeMobile"
            >
                <QuestionMarkCircleIcon class="h-5 w-5" />
            </RouterLink>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
    BellAlertIcon,
    BoltIcon,
    BuildingOffice2Icon,
    ChartBarIcon,
    ChevronLeftIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    Cog6ToothIcon,
    DevicePhoneMobileIcon,
    DocumentTextIcon,
    HomeIcon,
    LifebuoyIcon,
    PlusIcon,
    PuzzlePieceIcon,
    PresentationChartLineIcon,
    QuestionMarkCircleIcon,
    QueueListIcon,
    ScaleIcon,
    ShieldCheckIcon,
    SparklesIcon,
    Squares2X2Icon,
    UserCircleIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const route = useRoute();

const collapsed = computed(() => appStore.sidebarCollapsed);

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
            { name: 'compliance.dashboard', label: 'Compliance', to: { name: 'compliance.dashboard' }, icon: ScaleIcon, match: ['compliance.'] },
            { name: 'analytics.dashboard', label: 'Analytics', to: { name: 'analytics.dashboard' }, icon: ChartBarIcon, match: ['analytics.'] },
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

function closeMobile() {
    if (window.matchMedia('(max-width: 1023px)').matches) {
        appStore.closeSidebar();
    }
}

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
