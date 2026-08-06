<template>
  <div class="relative">
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!timeline.length"
      title="No timeline events"
      description="Containment, recovery, and notification actions will appear here."
    />
    <ol v-else class="space-y-4 border-l border-slate-200 pl-4">
      <li v-for="item in timeline" :key="item.uuid" class="relative">
        <span class="absolute -left-[21px] mt-1.5 h-2.5 w-2.5 rounded-full bg-brand-600" />
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-medium text-slate-900">{{ item.title }}</p>
            <p class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
          </div>
          <p class="mt-1 text-xs text-slate-500">
            {{ item.action_type_label || item.action_type }}
            <span v-if="item.from_status || item.to_status">
              · {{ item.from_status || '—' }} → {{ item.to_status || '—' }}
            </span>
          </p>
          <p v-if="item.description" class="mt-2 text-sm text-slate-600">{{ item.description }}</p>
          <p v-if="item.performer" class="mt-1 text-xs text-slate-500">
            By {{ item.performer.full_name }}
          </p>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup>
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  timeline: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
