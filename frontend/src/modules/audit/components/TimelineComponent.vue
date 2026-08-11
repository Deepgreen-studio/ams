<template>
  <ol class="relative space-y-5 border-l border-zinc-200 pl-6">
    <li v-for="item in items" :key="item.id || item.uuid" class="relative">
      <span
        class="absolute -left-[1.95rem] mt-1.5 h-3 w-3 rounded-full bg-brand-500 ring-4 ring-white"
      />
      <p class="text-sm font-semibold text-slate-900">
        {{ item.description || item.event || item.message }}
      </p>
      <p class="mt-0.5 text-xs text-slate-500">
        {{ item.module || item.exception || item.method }} —
        {{ formatDate(item.created_at || item.login_at) }}
      </p>
    </li>
    <li v-if="!items.length" class="text-sm text-slate-500">No timeline events.</li>
  </ol>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
