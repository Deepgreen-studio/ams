<template>
    <div class="rounded-xl border border-red-200 bg-red-50 px-6 py-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
            <ExclamationTriangleIcon class="h-6 w-6" />
        </div>
        <h3 class="text-lg font-semibold text-red-900">{{ title }}</h3>
        <p class="mt-2 text-sm text-red-700">{{ message }}</p>
        <div v-if="$slots.action" class="mt-6">
            <slot name="action" />
        </div>
    </div>
</template>

<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: {
        type: String,
        default: 'Something went wrong',
    },
    message: {
        type: String,
        default: 'An unexpected error occurred. Please try again.',
    },
});
</script>
