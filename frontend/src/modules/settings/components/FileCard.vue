<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <p class="truncate text-sm font-medium text-slate-900">{{ file?.original_name }}</p>
    <p class="mt-1 text-xs text-slate-500">{{ file?.human_size }} · {{ file?.extension }}</p>
  </div>
</template>

<script setup>
defineProps({ file: { type: Object, default: null } });
</script>
