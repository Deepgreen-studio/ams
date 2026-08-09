<template>
  <div>
    <!-- <PageHeader title="Scheduler Logs" description="Detailed log lines from scheduled job executions." /> -->
    <SchedulerSubnav />
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="filters.level" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All levels</option>
        <option value="info">Info</option>
        <option value="error">Error</option>
        <option value="warning">Warning</option>
      </select>
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search messages..."
        class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" @click="load">
        Apply
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">When</th>
            <th class="px-4 py-3">Job</th>
            <th class="px-4 py-3">Level</th>
            <th class="px-4 py-3">Message</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="4" class="px-4 py-8 text-center text-slate-500">Loading...</td>
          </tr>
          <tr v-else-if="!store.logs.length">
            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No logs yet.</td>
          </tr>
          <tr v-for="log in store.logs" :key="log.uuid">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(log.created_at) }}</td>
            <td class="px-4 py-3 text-slate-800">{{ log.run?.job?.name || '—' }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ log.level }}</span>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ log.message }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
const filters = reactive({ level: '', search: '' });

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function load() {
  await store.fetchLogs({ ...filters });
}

onMounted(load);
</script>
