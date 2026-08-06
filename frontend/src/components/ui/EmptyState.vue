<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <div class="mb-4 rounded-full bg-slate-100 p-3 text-slate-500">
            <InboxIcon class="h-8 w-8" />
        </div>
        <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
        <p class="mt-2 max-w-md text-sm text-slate-500">{{ description }}</p>
        <div v-if="$slots.action" class="mt-6">
            <slot name="action" />
        </div>
    </div>
</template>

<script setup>
import { InboxIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: {
        type: String,
        default: 'No data available',
    },
    description: {
        type: String,
        default: 'There is nothing to display here yet.',
    },
});
</script>
