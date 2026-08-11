<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="load"
      >
        Refresh
      </button>
    </Teleport>

    <MonitoringSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !queue" class="h-32 animate-pulse rounded-[12px] bg-zinc-100" />

    <template v-else-if="queue">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="card in cards"
          :key="card.label"
          class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight capitalize text-slate-900">
            {{ card.value }}
          </p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Running jobs</h3>
          </div>
          <table class="min-w-full text-left text-sm">
            <thead class="bg-zinc-50/80 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-5 py-3">Job</th>
                <th class="px-5 py-3">Queue</th>
                <th class="px-5 py-3">Attempts</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
              <tr v-if="!(queue.jobs?.running || []).length">
                <td colspan="3" class="px-5 py-8 text-center text-slate-500">No running jobs</td>
              </tr>
              <tr
                v-for="job in queue.jobs?.running || []"
                :key="job.uuid"
                class="hover:bg-zinc-50/80"
              >
                <td class="px-5 py-3 font-medium text-slate-900">
                  {{ job.display_name || job.job_class }}
                </td>
                <td class="px-5 py-3 text-slate-600">{{ job.queue }}</td>
                <td class="px-5 py-3 text-slate-600">{{ job.attempts }}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Recent failures</h3>
          </div>
          <table class="min-w-full text-left text-sm">
            <thead class="bg-zinc-50/80 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-5 py-3">Job</th>
                <th class="px-5 py-3">When</th>
                <th class="px-5 py-3">Error</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
              <tr v-if="!(queue.jobs?.recent_failed || []).length">
                <td colspan="3" class="px-5 py-8 text-center text-slate-500">No recent failures</td>
              </tr>
              <tr
                v-for="job in queue.jobs?.recent_failed || []"
                :key="job.uuid"
                class="hover:bg-zinc-50/80"
              >
                <td class="px-5 py-3 font-medium text-slate-900">
                  {{ job.display_name || job.job_class }}
                </td>
                <td class="px-5 py-3 text-slate-500">{{ job.failed_at || '—' }}</td>
                <td class="px-5 py-3 text-rose-600">{{ truncate(job.exception) }}</td>
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
