<template>
  <div>
    <PageHeader title="Queue Monitor" description="Queue depth, worker health, and job status.">
      <template #actions>
        <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Refresh</button>
      </template>
    </PageHeader>
    <MonitoringSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !queue" class="h-32 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="queue">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold capitalize text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Running jobs</h3>
          <table class="mt-3 min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th class="px-3 py-2">Job</th>
                <th class="px-3 py-2">Queue</th>
                <th class="px-3 py-2">Attempts</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!(queue.jobs?.running || []).length">
                <td colspan="3" class="px-3 py-6 text-center text-slate-500">No running jobs</td>
              </tr>
              <tr v-for="job in queue.jobs?.running || []" :key="job.uuid" class="border-t border-slate-100">
                <td class="px-3 py-2">{{ job.display_name || job.job_class }}</td>
                <td class="px-3 py-2">{{ job.queue }}</td>
                <td class="px-3 py-2">{{ job.attempts }}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Recent failures</h3>
          <table class="mt-3 min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th class="px-3 py-2">Job</th>
                <th class="px-3 py-2">When</th>
                <th class="px-3 py-2">Error</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!(queue.jobs?.recent_failed || []).length">
                <td colspan="3" class="px-3 py-6 text-center text-slate-500">No recent failures</td>
              </tr>
              <tr v-for="job in queue.jobs?.recent_failed || []" :key="job.uuid" class="border-t border-slate-100">
                <td class="px-3 py-2">{{ job.display_name || job.job_class }}</td>
                <td class="px-3 py-2 text-slate-500">{{ job.failed_at || '—' }}</td>
                <td class="px-3 py-2 text-rose-600">{{ truncate(job.exception) }}</td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const queue = computed(() => store.queueMonitor);

const cards = computed(() => [
  { label: 'Status', value: queue.value?.status || '—' },
  { label: 'Health', value: queue.value?.health_score ?? '—' },
  { label: 'Pending', value: queue.value?.pending ?? 0 },
  { label: 'Failed', value: queue.value?.failed ?? 0 },
  { label: 'Running', value: queue.value?.running ?? 0 },
]);

function truncate(value) {
  if (!value) return '—';
  return value.length > 80 ? `${value.slice(0, 80)}…` : value;
}

async function load() {
  await store.fetchQueueMonitor();
}

onMounted(load);
</script>
