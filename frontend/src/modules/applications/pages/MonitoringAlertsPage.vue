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
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          Crash dashboard
        </RouterLink>
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="openModal"
        >
          Create alert
        </button>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div class="mb-4 grid gap-4 sm:grid-cols-2">
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

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <h3 class="text-base font-semibold text-slate-900">Alert rules</h3>
          <div class="flex items-center gap-3">
            <p class="text-xs text-slate-500">{{ monitoringStore.alerts.length || 0 }} rules</p>
            <button
              type="button"
              class="rounded-[12px] bg-brand-600 px-3.5 py-1.5 text-xs font-medium text-white hover:bg-brand-700"
              @click="openModal"
            >
              Create
            </button>
          </div>
        </div>

        <div v-if="monitoringStore.loading" class="space-y-3 px-6 py-5">
          <div v-for="n in 3" :key="n" class="h-14 animate-pulse rounded-[12px] bg-slate-100" />
        </div>

        <EmptyState
          v-else-if="!monitoringStore.alerts.length"
          title="No alerts"
          description="Create a threshold rule to monitor app health."
          class="px-6 py-10"
        >
          <template #action>
            <button
              type="button"
              class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
              @click="openModal"
            >
              Create alert
            </button>
          </template>
        </EmptyState>

        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="alert in monitoringStore.alerts"
            :key="alert.uuid"
            class="flex items-start justify-between gap-3 px-6 py-4 transition hover:bg-zinc-50/60"
          >
            <div class="min-w-0">
              <p class="font-semibold text-slate-900">{{ alert.name }}</p>
              <p class="mt-1 text-sm text-slate-500">
                {{ alert.metric_label || alert.metric }}
                {{ operatorLabel(alert.operator) }}
                {{ alert.threshold }}
              </p>
            </div>
            <span
              class="inline-flex shrink-0 items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
              :class="severityClasses(alert.severity)"
            >
              <span class="h-1.5 w-1.5 rounded-full" :class="severityDot(alert.severity)" />
              {{ severityLabel(alert.severity) }}
            </span>
          </li>
        </ul>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <h3 class="text-base font-semibold text-slate-900">Triggered events</h3>
          <p class="text-xs text-slate-500">{{ monitoringStore.alertEvents.length || 0 }} events</p>
        </div>

        <div v-if="monitoringStore.loading" class="space-y-3 px-6 py-5">
          <div v-for="n in 3" :key="n" class="h-14 animate-pulse rounded-[12px] bg-slate-100" />
        </div>

        <EmptyState
          v-else-if="!monitoringStore.alertEvents.length"
          title="No events"
          description="Triggered alerts will appear here."
          class="px-6 py-10"
        />

        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="event in monitoringStore.alertEvents"
            :key="event.uuid"
            class="flex items-start justify-between gap-3 px-6 py-4 transition hover:bg-zinc-50/60"
          >
            <div class="min-w-0">
              <p class="font-semibold text-slate-900">{{ event.alert?.name || event.metric }}</p>
              <p class="mt-1 text-sm text-slate-500">
                Observed {{ event.observed_value }} (threshold {{ event.threshold }}) ·
                {{ event.status }}
              </p>
            </div>
            <button
              v-if="event.status === 'open'"
              type="button"
              class="shrink-0 rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50 disabled:opacity-60"
              :disabled="monitoringStore.saving"
              @click="onAck(event.uuid)"
            >
              Acknowledge
            </button>
          </li>
        </ul>
      </div>
    </div>

    <AlertFormModal
      :open="modalOpen"
      :loading="monitoringStore.saving"
      @cancel="closeModal"
      @submit="onCreate"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { BellAlertIcon, BoltIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import AlertFormModal from '@/modules/applications/components/AlertFormModal.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const toast = useToast();
const modalOpen = ref(false);

const operatorOptions = [
  { value: 'gte', label: '≥' },
  { value: 'gt', label: '>' },
  { value: 'lte', label: '≤' },
  { value: 'lt', label: '<' },
  { value: 'eq', label: '=' },
];

const severityOptions = [
  { value: 'info', label: 'Info' },
  { value: 'warning', label: 'Warning' },
  { value: 'critical', label: 'Critical' },
];

const summaryCards = computed(() => [
  {
    label: 'Alert rules',
    value: monitoringStore.alerts.length || 0,
    icon: BellAlertIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Open events',
    value: (monitoringStore.alertEvents || []).filter((e) => e.status === 'open').length,
    icon: BoltIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
]);

function operatorLabel(operator) {
  return operatorOptions.find((o) => o.value === operator)?.label || operator;
}

function severityLabel(severity) {
  return severityOptions.find((o) => o.value === severity)?.label || severity;
}

function severityClasses(severity) {
  switch (severity) {
    case 'critical':
      return 'border-rose-500 text-rose-700';
    case 'warning':
      return 'border-amber-500 text-amber-700';
    default:
      return 'border-sky-500 text-sky-700';
  }
}

function severityDot(severity) {
  switch (severity) {
    case 'critical':
      return 'bg-rose-500';
    case 'warning':
      return 'bg-amber-500';
    default:
      return 'bg-sky-500';
  }
}

function openModal() {
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
}

watch(
  () => monitoringStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load alerts');
  },
);

watch(
  () => monitoringStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

onMounted(() => monitoringStore.fetchAlerts(route.params.id));

async function onCreate(payload) {
  await monitoringStore.createAlert(route.params.id, payload);
  closeModal();
}

async function onAck(eventId) {
  await monitoringStore.acknowledgeEvent(route.params.id, eventId);
}
</script>
