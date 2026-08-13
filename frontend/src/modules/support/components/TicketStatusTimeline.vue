<template>
  <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
    <div class="mb-4">
      <h3 class="text-sm font-semibold text-slate-900">Status timeline</h3>
      <p class="mt-0.5 text-xs text-slate-500">Workflow history for this ticket</p>
    </div>
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <div v-else-if="!history.length" class="py-8 text-center">
      <p class="text-sm font-medium text-slate-900">No timeline events</p>
      <p class="mt-1 text-xs text-slate-500">Status and assignment changes will appear here.</p>
    </div>
    <ol v-else class="space-y-4">
      <li v-for="(item, index) in history" :key="item.uuid" class="flex gap-3">
        <div class="relative flex w-8 shrink-0 flex-col items-center">
          <span class="z-10 mt-1 h-3 w-3 rounded-full bg-brand-600" />
          <span v-if="index < history.length - 1" class="absolute top-4 h-full w-px bg-zinc-200" />
        </div>
        <div class="min-w-0 flex-1 pb-2">
          <p class="text-sm font-medium text-slate-900">
            {{ item.action_label || item.action }}
            <span class="font-normal text-slate-500">
              · {{ item.from_status_label || '—' }} → {{ item.to_status_label || item.to_status || '—' }}
            </span>
          </p>
          <p class="mt-1 text-xs text-slate-500">
            {{ formatDate(item.created_at) }}
            <span v-if="item.actor?.full_name"> · {{ item.actor.full_name }}</span>
          </p>
          <p
            v-if="item.comments"
            class="mt-2 whitespace-pre-wrap rounded-[12px] bg-zinc-50 px-3 py-2 text-sm text-slate-700"
          >
            {{ item.comments }}
          </p>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup>
defineProps({
  history: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
