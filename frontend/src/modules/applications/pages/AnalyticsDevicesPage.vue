<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-for="link in navLinks"
          :key="link.name"
          :to="{ name: link.name, params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] px-5 py-2.5 text-sm font-medium transition"
          :class="
            isActive(link.name)
              ? 'bg-brand-600 text-white hover:bg-brand-700'
              : 'border border-zinc-200 text-slate-700 hover:bg-zinc-50'
          "
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.loading && !analyticsStore.devices.length"
      class="grid gap-4 lg:grid-cols-2"
    >
      <div v-for="n in 2" :key="n" class="h-72 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <h3 class="text-base font-semibold text-slate-900">Devices</h3>
          <p class="text-xs text-slate-500">{{ analyticsStore.devices.length || 0 }} models</p>
        </div>

        <EmptyState
          v-if="!analyticsStore.devices.length"
          title="No devices"
          description="Ingest device analytics to populate this table."
          class="px-6 py-10"
        />

        <div v-else class="overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Device</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">OS</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Users</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in analyticsStore.devices"
                :key="`${item.device_model}-${index}`"
                class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
              >
                <td class="px-5 py-4 font-semibold text-slate-900">
                  {{ item.device_model || 'Unknown' }}
                </td>
                <td class="px-5 py-4 text-slate-600">
                  {{ item.os_name }} {{ item.os_version }}
                </td>
                <td class="px-5 py-4 text-slate-700">{{ item.users }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <h3 class="text-base font-semibold text-slate-900">OS versions</h3>
          <p class="text-xs text-slate-500">{{ analyticsStore.osVersions.length || 0 }} versions</p>
        </div>

        <EmptyState
          v-if="!analyticsStore.osVersions.length"
          title="No OS data"
          description="Ingest OS analytics to populate this table."
          class="px-6 py-10"
        />

        <div v-else class="overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">OS</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Version</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Users</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in analyticsStore.osVersions"
                :key="`${item.os_name}-${item.os_version}-${index}`"
                class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
              >
                <td class="px-5 py-4 font-semibold text-slate-900">
                  {{ item.os_name || 'Unknown' }}
                </td>
                <td class="px-5 py-4 text-slate-600">{{ item.os_version || '—' }}</td>
                <td class="px-5 py-4 text-slate-700">{{ item.users }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const analyticsStore = useAnalyticsStore();
const toast = useToast();

const navLinks = [
  { name: 'applications.analytics', label: 'Dashboard' },
  { name: 'applications.analytics.trends', label: 'Trends' },
  { name: 'applications.analytics.heatmap', label: 'Heatmap' },
  { name: 'applications.analytics.countries', label: 'Countries' },
  { name: 'applications.analytics.devices', label: 'Devices' },
];

function isActive(name) {
  return route.name === name;
}

watch(
  () => analyticsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load device analytics');
  },
);

onMounted(() => analyticsStore.fetchDevices(route.params.id));
</script>
