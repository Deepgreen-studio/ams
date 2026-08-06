<template>
  <div class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="mb-4">
      <h3 class="text-sm font-semibold text-slate-900">Workflow timeline</h3>
      <p class="text-xs text-slate-500">Stage transitions, approvals, escalations, and decisions.</p>
    </div>
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
    </div>
    <div v-else-if="!logs.length" class="py-8 text-center text-sm text-slate-500">
      No timeline events yet.
    </div>
    <ol v-else class="space-y-4">
      <li v-for="(item, index) in logs" :key="item.uuid" class="flex gap-3">
        <div class="relative flex w-8 shrink-0 flex-col items-center">
          <span
            class="z-10 mt-1 h-3 w-3 rounded-full"
            :class="dotClass(item.action)"
          />
          <span v-if="index < logs.length - 1" class="absolute top-4 h-full w-px bg-slate-200" />
        </div>
        <div class="min-w-0 flex-1 pb-2">
          <p class="text-sm font-medium text-slate-900">
            {{ item.action_label || item.action }}
            <span v-if="item.step?.name" class="font-normal text-slate-500">· {{ item.step.name }}</span>
          </p>
          <p class="mt-1 text-xs text-slate-500">
            {{ formatDate(item.created_at) }}
            <span v-if="item.actor?.full_name"> · {{ item.actor.full_name }}</span>
            <span v-if="item.from_status || item.to_status">
              · {{ item.from_status || '—' }} → {{ item.to_status || '—' }}
            </span>
          </p>
          <p
            v-if="item.comment"
            class="mt-2 whitespace-pre-wrap rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700"
          >
            {{ item.comment }}
          </p>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup>
defineProps({
  logs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function dotClass(action) {
  if (action === 'approved' || action === 'completed') return 'bg-emerald-500';
  if (action === 'rejected' || action === 'cancelled' || action === 'timed_out') return 'bg-rose-500';
  if (action === 'escalated') return 'bg-amber-500';
  return 'bg-brand-600';
}
</script>
