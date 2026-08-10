<template>
  <div class="rounded-[12px] bg-white p-6 sm:p-8">
    <h3 class="text-base font-semibold text-slate-900">Application timeline</h3>

    <div v-if="loading" class="mt-5 space-y-3">
      <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!items.length"
      title="No timeline events"
      description="Assignment activity will appear here."
      class="py-10"
    />

    <ol v-else class="relative mt-6 space-y-5 border-l border-zinc-100 pl-6">
      <li v-for="item in items" :key="item.id" class="relative">
        <span
          class="absolute -left-[1.55rem] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-brand-500 ring-1 ring-brand-200"
        />
        <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <p class="text-sm font-medium text-slate-900">{{ item.description }}</p>
            <time class="shrink-0 text-xs text-slate-500">{{ formatDate(item.created_at) }}</time>
          </div>
          <p class="mt-1 text-xs text-slate-500">
            {{ item.causer?.full_name || 'System' }}
            <span v-if="item.properties?.status">
              · {{ formatLabel(item.properties.status) }}
            </span>
            <span v-if="item.properties?.ownership_type">
              · {{ formatLabel(item.properties.ownership_type) }}
            </span>
          </p>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup>
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function formatLabel(value) {
  return String(value || '')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>
