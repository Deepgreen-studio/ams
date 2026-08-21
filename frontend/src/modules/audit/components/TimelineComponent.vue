<template>
  <ol v-if="items.length" class="relative space-y-5 border-l border-zinc-200 pl-6">
    <li v-for="item in items" :key="item.id || item.uuid" class="relative">
      <span class="absolute -left-[1.95rem] mt-1.5 h-3 w-3 rounded-full bg-brand-500 ring-4 ring-white" />
      <p class="text-sm font-semibold text-slate-900">
        {{ item.description || item.event || item.message }}
      </p>
      <p class="mt-0.5 text-xs text-slate-500">
        {{ formatLabel(item.module || item.action) }}
        <span v-if="item.module || item.action"> · </span>
        {{ formatDate(item.created_at || item.login_at) }}
      </p>
    </li>
  </ol>
  <p v-else class="text-sm text-slate-500">No timeline events on this page.</p>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function formatLabel(value) {
  if (!value) return '';
  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}
</script>
