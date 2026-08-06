<template>
  <div class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="mb-4 flex items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">Workflow timeline</h3>
        <p class="text-xs text-slate-500">
          Approval levels: Writer → Editor → Manager → Administrator
        </p>
      </div>
    </div>
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!history.length"
      title="No workflow history"
      description="Submit this content for review to start the approval workflow."
    />
    <ol v-else class="space-y-4">
      <li v-for="(item, index) in history" :key="item.uuid" class="flex gap-3">
        <div class="relative flex w-8 shrink-0 flex-col items-center">
          <span class="z-10 mt-1 h-3 w-3 rounded-full bg-brand-600" />
          <span v-if="index < history.length - 1" class="absolute top-4 h-full w-px bg-slate-200" />
        </div>
        <div class="min-w-0 flex-1 pb-2">
          <p class="text-sm font-medium text-slate-900">
            {{ actionLabel(item.action) }}
            <span class="font-normal text-slate-500"
              >· {{ item.from_status || '—' }} → {{ item.to_status }}</span
            >
          </p>
          <p class="mt-1 text-xs text-slate-500">
            {{ formatDate(item.created_at) }}
            <span v-if="item.actor?.full_name"> · {{ item.actor.full_name }}</span>
            <span v-if="item.approval_level"> · {{ levelLabel(item.approval_level) }}</span>
          </p>
          <p
            v-if="item.comments"
            class="mt-2 whitespace-pre-wrap rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700"
          >
            {{ item.comments }}
          </p>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup>
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  history: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function actionLabel(action) {
  return String(action || '').replaceAll('_', '');
}

function levelLabel(level) {
  const map = {
    writer: 'Content Writer',
    editor: 'Editor',
    manager: 'Manager',
    administrator: 'Administrator',
  };
  return map[level] || level;
}
</script>
