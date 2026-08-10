<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">
                    {{ appStore.appName }}
                </p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                    Secure Access
                </h1>
            </div>

            <div class=" bg-white p-6">
                <RouterView />
            </div>
        </div>
    </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
</script>
