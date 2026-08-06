<template>
    <aside
        class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col"
        :class="{ 'lg:flex': appStore.sidebarOpen }"
    >
        <div class="flex h-16 items-center border-b border-slate-200 px-6">
            <span class="text-lg font-semibold text-slate-900">{{ appStore.appName }}</span>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4">
            <RouterLink
                v-for="item in navigation"
                :key="item.name"
                :to="item.to"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                active-class="bg-brand-50 text-brand-700 hover:bg-brand-50 hover:text-brand-700"
            >
                <component :is="item.icon" class="h-5 w-5" />
                <span>{{ item.label }}</span>
            </RouterLink>
        </nav>

        <div class="border-t border-slate-200 px-4 py-4 text-xs text-slate-500">
            Enterprise Platform Foundation
        </div>
    </aside>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { HomeIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();

const navigation = [
    { name: 'dashboard', label: 'Dashboard', to: { name: 'dashboard' }, icon: HomeIcon },
    { name: 'modules', label: 'Modules', to: { name: 'dashboard' }, icon: Squares2X2Icon },
];
</script>
