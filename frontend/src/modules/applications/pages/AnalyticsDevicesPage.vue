<template>
  <div>
    <PageHeader
      title="Device & OS statistics"
      description="Breakdown by device model and operating system versions."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.analytics', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Dashboard</RouterLink
        >
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ analyticsStore.error }}
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold">Devices</div>
        <EmptyState
          v-if="!analyticsStore.devices.length"
          title="No devices"
          description="Ingest device analytics to populate this table."
        />
        <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Device</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">OS</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Users</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(item, index) in analyticsStore.devices"
              :key="`${item.device_model}-${index}`"
            >
              <td class="px-4 py-3 font-medium text-slate-900">
                {{ item.device_model || 'Unknown' }}
              </td>
              <td class="px-4 py-3 text-slate-600">{{ item.os_name }} {{ item.os_version }}</td>
              <td class="px-4 py-3 text-slate-700">{{ item.users }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold">OS versions</div>
        <EmptyState
          v-if="!analyticsStore.osVersions.length"
          title="No OS data"
          description="Ingest OS analytics to populate this table."
        />
        <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">OS</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Version</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Users</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(item, index) in analyticsStore.osVersions"
              :key="`${item.os_name}-${item.os_version}-${index}`"
            >
              <td class="px-4 py-3 font-medium text-slate-900">{{ item.os_name || 'Unknown' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ item.os_version || '—' }}</td>
              <td class="px-4 py-3 text-slate-700">{{ item.users }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';

const route = useRoute();
const analyticsStore = useAnalyticsStore();

onMounted(() => analyticsStore.fetchDevices(route.params.id));
</script>
