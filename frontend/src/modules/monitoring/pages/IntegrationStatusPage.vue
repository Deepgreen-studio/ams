<template>
  <div>
    <!-- <PageHeader title="Integration Status" description="Per-integration health and availability.">
      <template #actions>
        <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Refresh</button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Refresh</button>
    </Teleport>
    <MonitoringSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="data" class="mb-6 grid gap-4 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Server status</p>
        <p class="mt-2 text-lg font-semibold capitalize" :class="statusClass(data.server_status)">{{ data.server_status }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Healthy</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">
          {{ data.summary?.integrations_healthy ?? 0 }} / {{ data.summary?.integrations_total ?? 0 }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Uptime checks</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">{{ data.summary?.uptime_percent ?? 0 }}%</p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
          <tr>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Health</th>
            <th class="px-4 py-3">Last check</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading && !(data?.integrations || []).length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading…</td>
          </tr>
          <tr v-else-if="!(data?.integrations || []).length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No integrations found.</td>
          </tr>
          <tr v-for="item in data?.integrations || []" :key="item.uuid" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.base_url }}</p>
            </td>
            <td class="px-4 py-3 capitalize">{{ item.type }}</td>
            <td class="px-4 py-3 capitalize">{{ item.status }}</td>
            <td class="px-4 py-3">
              <span class="rounded-md px-2 py-1 text-xs font-medium capitalize" :class="badgeClass(item.health_status)">
                {{ item.health_status }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-500">{{ item.last_health_check || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const data = computed(() => store.integrations);

function statusClass(value) {
  if (value === 'healthy') return 'text-emerald-700';
  if (value === 'degraded') return 'text-amber-700';
  if (value === 'unhealthy') return 'text-rose-700';
  return 'text-slate-700';
}

function badgeClass(value) {
  if (value === 'healthy') return 'bg-emerald-50 text-emerald-700';
  if (value === 'degraded') return 'bg-amber-50 text-amber-700';
  if (value === 'unhealthy') return 'bg-rose-50 text-rose-700';
  return 'bg-slate-100 text-slate-700';
}

async function load() {
  await store.fetchIntegrations();
}

onMounted(load);
</script>
