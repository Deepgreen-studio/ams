<template>
  <div>
    <PageHeader
      title="Automation History"
      description="Execution logs for automation rules across the platform."
    />

    <AutomationSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in statCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All statuses</option>
        <option value="success">Success</option>
        <option value="failed">Failed</option>
        <option value="skipped">Skipped</option>
        <option value="partial">Partial</option>
      </select>
      <input
        v-model="filters.event_key"
        type="search"
        placeholder="Filter by event key"
        class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="load"
      >
        Apply
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">When</th>
            <th class="px-4 py-3">Rule</th>
            <th class="px-4 py-3">Event</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Message</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading history...</td>
          </tr>
          <tr v-else-if="!store.logs.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No automation runs yet.</td>
          </tr>
          <tr v-for="log in store.logs" :key="log.uuid">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(log.created_at) }}</td>
            <td class="px-4 py-3 font-medium text-slate-900">{{ log.rule?.name || '—' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ log.event_key || log.trigger_type || '—' }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2.5 py-1 text-xs font-medium"
                :class="statusClass(log.status)"
              >
                {{ log.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ log.message || log.error_message || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AutomationSubnav from '@/modules/automation/components/AutomationSubnav.vue';
import { useAutomationStore } from '@/modules/automation/stores/automation';

const store = useAutomationStore();
const filters = reactive({
  status: '',
  event_key: '',
});

const statCards = computed(() => [
  { label: 'Total runs', value: store.logStatistics?.total ?? 0 },
  { label: 'Success', value: store.logStatistics?.success ?? 0 },
  { label: 'Failed', value: store.logStatistics?.failed ?? 0 },
  { label: 'Skipped', value: store.logStatistics?.skipped ?? 0 },
]);

function statusClass(status) {
  if (status === 'success') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'skipped') return 'bg-slate-100 text-slate-600';
  return 'bg-amber-50 text-amber-700';
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function load() {
  await store.fetchLogs({ ...filters });
}

onMounted(load);
</script>
