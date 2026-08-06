<template>
    <div class="min-h-screen bg-slate-50">
        <LoadingScreen v-if="appStore.isLoading" />

        <div class="flex min-h-screen">
            <Sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <TopNavigation />

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <Breadcrumb class="mb-4" />
                    <RouterView />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
import { useAppStore } from '@/stores/app';
import Sidebar from '@/components/layout/Sidebar.vue';
import TopNavigation from '@/components/layout/TopNavigation.vue';
import Breadcrumb from '@/components/ui/Breadcrumb.vue';
import LoadingScreen from '@/components/ui/LoadingScreen.vue';

const appStore = useAppStore();
</script>
