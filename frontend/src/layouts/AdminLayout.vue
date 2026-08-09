<template>
    <div class="min-h-screen bg-canvas">
        <LoadingScreen v-if="appStore.isLoading" />

        <div class="flex min-h-screen">
            <Sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <TopNavigation />

                <main class="flex-1 px-4 pb-8 pt-2 sm:px-6 lg:px-8">
                    <div
                        v-if="showToolbar"
                        class="mb-[30px] mt-[16px] flex flex-wrap items-center justify-between gap-3"
                    >
                        <Breadcrumb
                            v-if="showBreadcrumb"
                            class="min-w-0"
                        />
                        <div
                            id="page-header-actions"
                            class="flex flex-wrap items-center justify-end gap-2"
                            :class="showBreadcrumb ? 'ml-auto' : 'w-full'"
                        />
                    </div>
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
const showToolbar = computed(() => showBreadcrumb.value);
</script>
