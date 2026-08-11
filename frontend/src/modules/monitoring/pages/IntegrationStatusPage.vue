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

    <div v-if="data" class="mb-4 grid gap-4 sm:grid-cols-3">
      <div class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Server status</p>
        <p class="mt-1 text-xl font-bold capitalize" :class="statusClass(data.server_status)">
          {{ data.server_status }}
        </p>
      </div>
      <div class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Healthy</p>
        <p class="mt-1 text-xl font-bold text-slate-900">
          {{ data.summary?.integrations_healthy ?? 0 }} /
          {{ data.summary?.integrations_total ?? 0 }}
        </p>
      </div>
      <div class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Uptime checks</p>
        <p class="mt-1 text-xl font-bold text-slate-900">
          {{ data.summary?.uptime_percent ?? 0 }}%
        </p>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-zinc-100 bg-zinc-50/80 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-5 py-3.5">Name</th>
            <th class="px-5 py-3.5">Type</th>
            <th class="px-5 py-3.5">Status</th>
            <th class="px-5 py-3.5">Health</th>
            <th class="px-5 py-3.5">Last check</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
          <tr v-if="store.loading && !(data?.integrations || []).length">
            <td colspan="5" class="px-5 py-8 text-center text-slate-500">Loading…</td>
          </tr>
          <tr v-else-if="!(data?.integrations || []).length">
            <td colspan="5" class="px-5 py-8 text-center text-slate-500">No integrations found.</td>
          </tr>
          <tr
            v-for="item in data?.integrations || []"
            :key="item.uuid"
            class="hover:bg-zinc-50/80"
          >
            <td class="px-5 py-3.5">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.base_url }}</p>
            </td>
            <td class="px-5 py-3.5 capitalize text-slate-600">{{ item.type }}</td>
            <td class="px-5 py-3.5 capitalize text-slate-600">{{ item.status }}</td>
            <td class="px-5 py-3.5">
              <span
                class="rounded-lg px-2.5 py-1 text-xs font-medium capitalize"
                :class="badgeClass(item.health_status)"
              >
                {{ item.health_status }}
              </span>
            </td>
            <td class="px-5 py-3.5 text-slate-500">{{ item.last_health_check || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
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
  return 'bg-zinc-100 text-slate-700';
}

async function load() {
  await store.fetchIntegrations();
}

onMounted(load);
</script>
