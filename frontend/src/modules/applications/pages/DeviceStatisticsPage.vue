<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'applications.monitoring.health', params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          Health
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Crash dashboard
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
        <h3 class="text-base font-semibold text-slate-900">Device breakdown</h3>
        <p class="text-xs text-slate-500">{{ monitoringStore.devices.length || 0 }} models</p>
      </div>

      <div v-if="monitoringStore.loading" class="space-y-3 px-6 py-5">
        <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
      </div>

      <EmptyState
        v-else-if="!monitoringStore.devices.length"
        title="No device data"
        description="Device statistics appear after crash or API error reports are ingested."
        class="px-6 py-10"
      >
        <template #action>
          <RouterLink
            :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Open crash dashboard
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Device</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">OS</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Reports</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Unique devices</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in monitoringStore.devices"
              :key="`${item.device_model}-${index}`"
              class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            >
              <td class="px-5 py-4 font-semibold text-slate-900">
                {{ item.device_model || 'Unknown' }}
              </td>
              <td class="px-5 py-4 text-slate-600">
                {{ item.device_os || '—' }} {{ item.device_os_version || '' }}
              </td>
              <td class="px-5 py-4 text-slate-700">{{ item.total }}</td>
              <td class="px-5 py-4 text-slate-700">{{ item.devices }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  DevicePhoneMobileIcon,
  DocumentChartBarIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const toast = useToast();

const summaryCards = computed(() => {
  const rows = monitoringStore.devices || [];
  const reports = rows.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
  const unique = rows.reduce((sum, item) => sum + (Number(item.devices) || 0), 0);

  return [
    {
      label: 'Models',
      value: rows.length,
      icon: Squares2X2Icon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Reports',
      value: reports,
      icon: DocumentChartBarIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-600',
    },
    {
      label: 'Unique devices',
      value: unique,
      icon: DevicePhoneMobileIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-600',
    },
  ];
});

watch(
  () => monitoringStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load device statistics');
  },
);

onMounted(() => monitoringStore.fetchDevices(route.params.id));
</script>
