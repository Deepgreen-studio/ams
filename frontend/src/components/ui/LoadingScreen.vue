<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm">
        <div class="flex flex-col items-center gap-3">
            <div class="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-brand-600" />
            <p class="text-sm font-medium text-slate-600">{{ message }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: 'Loading...',
    },
});
</script>
