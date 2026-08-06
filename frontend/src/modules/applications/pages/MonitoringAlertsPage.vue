<template>
  <div>
    <PageHeader
      title="Monitoring alerts"
      description="Threshold rules for crash rate, health score, response time, and more."
    />
    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="monitoringStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ monitoringStore.error }}
    </div>
    <div
      v-if="monitoringStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ monitoringStore.successMessage }}
    </div>

    <form
      class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-3"
      @submit.prevent="onCreate"
    >
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Name</label
        >
        <input
          v-model="form.name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          required
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Metric</label
        >
        <select
          v-model="form.metric"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="health_score">Health score</option>
          <option value="crash_rate">Crash rate</option>
          <option value="anr_rate">ANR rate</option>
          <option value="api_error_rate">API error rate</option>
          <option value="response_time">Response time</option>
          <option value="memory">Memory</option>
          <option value="battery">Battery</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Operator</label
        >
        <select
          v-model="form.operator"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="gte">≥</option>
          <option value="gt">></option>
          <option value="lte">≤</option>
          <option value="lt">Less than</option>
          <option value="eq">=</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Threshold</label
        >
        <input
          v-model.number="form.threshold"
          type="number"
          step="0.01"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          required
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Severity</label
        >
        <select
          v-model="form.severity"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="info">Info</option>
          <option value="warning">Warning</option>
          <option value="critical">Critical</option>
        </select>
      </div>
      <div class="flex items-end">
        <button
          type="submit"
          class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="monitoringStore.saving"
        >
          Create alert
        </button>
      </div>
    </form>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold">Alert rules</div>
        <EmptyState
          v-if="!monitoringStore.alerts.length"
          title="No alerts"
          description="Create a threshold rule to monitor app health."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="alert in monitoringStore.alerts" :key="alert.uuid" class="px-4 py-3 text-sm">
            <p class="font-medium text-slate-900">{{ alert.name }}</p>
            <p class="mt-1 text-slate-500">
              {{ alert.metric_label }} {{ alert.operator }} {{ alert.threshold }} ·
              {{ alert.severity }}
            </p>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold">
          Triggered events
        </div>
        <EmptyState
          v-if="!monitoringStore.alertEvents.length"
          title="No events"
          description="Triggered alerts will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="event in monitoringStore.alertEvents"
            :key="event.uuid"
            class="px-4 py-3 text-sm"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-medium text-slate-900">{{ event.alert?.name || event.metric }}</p>
                <p class="mt-1 text-slate-500">
                  Observed {{ event.observed_value }} (threshold {{ event.threshold }}) ·
                  {{ event.status }}
                </p>
              </div>
              <button
                v-if="event.status === 'open'"
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-60"
                :disabled="monitoringStore.saving"
                @click="monitoringStore.acknowledgeEvent(route.params.id, event.uuid)"
              >
                Ack
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const form = reactive({
  name: '',
  metric: 'health_score',
  operator: 'lte',
  threshold: 70,
  severity: 'warning',
});

onMounted(() => monitoringStore.fetchAlerts(route.params.id));

async function onCreate() {
  await monitoringStore.createAlert(route.params.id, { ...form });
  form.name = '';
}
</script>
