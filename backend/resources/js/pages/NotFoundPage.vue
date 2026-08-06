<template>
    <div class="flex min-h-screen flex-col items-center justify-center px-4 text-center">
        <p class="text-6xl font-semibold text-slate-300">404</p>
        <h1 class="mt-4 text-2xl font-semibold text-slate-900">Page not found</h1>
        <p class="mt-2 max-w-md text-sm text-slate-500">
            The page you are looking for does not exist or has been moved.
        </p>
        <RouterLink
            :to="{ name: 'dashboard' }"
            class="mt-6 inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
            Back to dashboard
        </RouterLink>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>
