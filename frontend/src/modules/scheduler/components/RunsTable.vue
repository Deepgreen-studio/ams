<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
        <tr>
          <th class="px-4 py-3">When</th>
          <th class="px-4 py-3">Job</th>
          <th class="px-4 py-3">Trigger</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Duration</th>
          <th v-if="showRetry" class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <tr v-if="loading">
          <td :colspan="showRetry ? 6 : 5" class="px-4 py-8 text-center text-slate-500">Loading...</td>
        </tr>
        <tr v-else-if="!runs.length">
          <td :colspan="showRetry ? 6 : 5" class="px-4 py-8 text-center text-slate-500">No runs found.</td>
        </tr>
        <tr v-for="run in runs" :key="run.uuid">
          <td class="px-4 py-3 text-slate-600">{{ formatDate(run.created_at) }}</td>
          <td class="px-4 py-3">
            <p class="font-medium text-slate-900">{{ run.job?.name || '—' }}</p>
            <p class="text-xs text-slate-500">{{ run.job?.handler_key || '' }}</p>
            <p v-if="run.error_message" class="text-xs text-rose-600">{{ run.error_message }}</p>
          </td>
          <td class="px-4 py-3 text-slate-600">{{ run.trigger || '—' }}</td>
          <td class="px-4 py-3">
            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(run.status)">
              {{ run.status }}
            </span>
          </td>
          <td class="px-4 py-3 text-slate-600">
            {{ run.duration_ms != null ? `${run.duration_ms} ms` : '—' }}
          </td>
          <td v-if="showRetry" class="px-4 py-3 text-right">
            <button
              type="button"
              class="text-sm font-medium text-brand-700 hover:underline"
              @click="$emit('retry', run)"
            >
              Retry
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  runs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  showRetry: { type: Boolean, default: false },
});

defineEmits(['retry']);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function statusClass(status) {
  if (status === 'success') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'running' || status === 'queued') return 'bg-amber-50 text-amber-700';
  return 'bg-slate-100 text-slate-600';
}
</script>
