<template>
    <div class="min-h-screen bg-canvas">
        <LoadingScreen v-if="appStore.isLoading" />

        <div class="flex min-h-screen">
            <Sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <TopNavigation />

                <main class="flex-1 px-4 pb-8 pt-2 sm:px-6 lg:px-8">
                    <Breadcrumb
                        v-if="showBreadcrumb"
                        class="mb-4"
                    />
                    <RouterView />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import { useAppStore } from '@/stores/app';
import Sidebar from '@/components/layout/Sidebar.vue';
import TopNavigation from '@/components/layout/TopNavigation.vue';
import Breadcrumb from '@/components/ui/Breadcrumb.vue';
import LoadingScreen from '@/components/ui/LoadingScreen.vue';

const appStore = useAppStore();
const route = useRoute();

const showBreadcrumb = computed(() => route.name !== 'dashboard');
</script>
