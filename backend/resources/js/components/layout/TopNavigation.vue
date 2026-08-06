<template>
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden"
                    @click="appStore.toggleSidebar"
                >
                    <Bars3Icon class="h-5 w-5" />
                </button>
                <div>
                    <p class="text-sm font-medium text-slate-900">Administration</p>
                    <p class="text-xs text-slate-500">Application Management System</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 sm:inline-flex">
                    Phase 1.1 Foundation
                </span>
            </div>
        </div>
    </header>
</template>

<script setup>
import { Bars3Icon } from '@heroicons/vue/24/outline';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
</script>
