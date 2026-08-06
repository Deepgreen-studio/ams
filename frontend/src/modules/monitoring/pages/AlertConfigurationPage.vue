<template>
  <div>
    <PageHeader
      title="Alert Configuration"
      description="Configure thresholds for Integration Hub health metrics."
    />
    <MonitoringSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form
      class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 md:grid-cols-2"
      @submit.prevent="create"
    >
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input
          v-model="form.name"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          required
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Metric</label>
        <select
          v-model="form.metric"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="error_rate">Error rate</option>
          <option value="avg_response_ms">Avg response ms</option>
          <option value="health_score">Health score</option>
          <option value="performance_score">Performance score</option>
          <option value="uptime_percent">Uptime %</option>
          <option value="webhook_success_rate">Webhook success %</option>
          <option value="queue_health_score">Queue health</option>
          <option value="queue_failed">Queue failed</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Operator</label>
        <select
          v-model="form.operator"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="gte">≥</option>
          <option value="gt">></option>
          <option value="lte">≤</option>
          <option value="lt"><</option>
          <option value="eq">=</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Threshold</label>
        <input
          v-model.number="form.threshold"
          type="number"
          step="0.01"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          required
        />
      </div>
      <div class="md:col-span-2">
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
        >
          Create alert
        </button>
      </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Alert</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Rule</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Enabled</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.alerts" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">
                Cooldown triggered {{ formatDate(item.last_triggered_at) }}
              </p>
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ item.metric }} {{ item.operator }} {{ item.threshold }}
            </td>
            <td class="px-4 py-3">{{ item.is_enabled ? 'Yes' : 'No' }}</td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="mr-2 text-xs font-medium text-brand-700 hover:underline"
                :disabled="store.saving"
                @click="toggle(item)"
              >
                {{ item.is_enabled ? 'Disable' : 'Enable' }}
              </button>
              <button
                type="button"
                class="text-xs font-medium text-rose-700 hover:underline"
                :disabled="store.saving"
                @click="remove(item.uuid)"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="!store.alerts.length">
            <td colspan="4" class="px-4 py-10 text-center text-slate-500">No alerts configured.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
        Recent alert events
      </h2>
      <ul class="divide-y divide-slate-100 text-sm">
        <li v-for="event in store.alertEvents" :key="event.uuid" class="py-3">
          <p class="font-medium text-slate-900">{{ event.message }}</p>
          <p class="text-xs capitalize text-slate-500">
            {{ event.severity }} · {{ event.status }} · {{ formatDate(event.created_at) }}
          </p>
        </li>
        <li v-if="!store.alertEvents.length" class="py-6 text-center text-slate-500">
          No alert events.
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const form = reactive({
  name: '',
  metric: 'error_rate',
  operator: 'gte',
  threshold: 5,
});

onMounted(async () => {
  await Promise.all([store.fetchAlerts(), store.fetchAlertEvents()]);
});

async function create() {
  await store.createAlert({ ...form, channels: ['in_app'] });
  form.name = '';
  await store.fetchAlerts();
}

async function toggle(item) {
  await store.updateAlert(item.uuid, { is_enabled: !item.is_enabled });
  await store.fetchAlerts();
}

async function remove(id) {
  await store.deleteAlert(id);
  await store.fetchAlerts();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : 'never';
}
</script>
