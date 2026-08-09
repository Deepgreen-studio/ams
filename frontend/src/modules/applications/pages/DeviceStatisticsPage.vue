<template>
  <div>
    <!-- <PageHeader
      title="Device statistics"
      description="Crash and error distribution across device models and OS versions."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Crash dashboard</RouterLink
        >
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Crash dashboard</RouterLink
        >
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="monitoringStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ monitoringStore.error }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="monitoringStore.loading" class="space-y-3 p-4">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!monitoringStore.devices.length"
        title="No device data"
        description="Device statistics appear after crash or API error reports are ingested."
      />
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Device</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">OS</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Reports</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Unique devices</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="(item, index) in monitoringStore.devices"
            :key="`${item.device_model}-${index}`"
          >
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ item.device_model || 'Unknown' }}
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ item.device_os || '—' }} {{ item.device_os_version || '' }}
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.total }}</td>
            <td class="px-4 py-3 text-slate-700">{{ item.devices }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';

const route = useRoute();
const monitoringStore = useMonitoringStore();

onMounted(() => monitoringStore.fetchDevices(route.params.id));
</script>
