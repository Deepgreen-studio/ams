<template>
  <div>
    <PageHeader title="Security Dashboard" description="Failed logins, security events, API key usage, and risk posture.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="store.saving"
          @click="onCapture"
        >
          Capture snapshot
        </button>
      </template>
    </PageHeader>
    <AnalyticsSubnav />
    <SecurityAnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        From
        <input v-model="filters.from" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        To
        <input v-model="filters.to" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="mb-4 flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Risk level</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ data.risk?.level ?? '—' }}</p>
        </div>
        <div class="ml-auto text-right">
          <p class="text-xs text-slate-500">Score</p>
          <p class="text-3xl font-semibold" :class="riskColor">{{ data.risk?.score ?? 0 }}</p>
        </div>
      </div>

      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleLineChart title="Failed logins" :points="chartPoints(data.charts?.logins_failed)" value-key="value" stroke="#be123c" fill="#be123c" />
        <SimpleLineChart title="Security events" :points="chartPoints(data.charts?.security_events)" value-key="value" stroke="#b45309" fill="#b45309" />
        <SimpleLineChart title="API errors" :points="chartPoints(data.charts?.api_errors)" value-key="value" stroke="#7c3aed" fill="#7c3aed" />
        <SimpleLineChart title="Risk score" :points="chartPoints(data.charts?.risk_score)" value-key="value" stroke="#0369a1" fill="#0369a1" />
      </div>

      <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">Top failed login IPs</h3>
          <ul class="mt-3 divide-y divide-slate-100">
            <li v-for="row in data.failed_login_ips || []" :key="row.ip_address" class="flex justify-between py-2 text-sm">
              <span class="font-mono text-slate-700">{{ row.ip_address }}</span>
              <span class="text-slate-500">{{ row.count }}</span>
            </li>
            <li v-if="!(data.failed_login_ips || []).length" class="py-6 text-center text-sm text-slate-500">No failed logins in range.</li>
          </ul>
        </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
          <h3 class="text-sm font-semibold text-slate-900">API keys</h3>
          <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div><p class="text-slate-500">Total</p><p class="text-lg font-semibold">{{ data.api_keys?.total ?? 0 }}</p></div>
            <div><p class="text-slate-500">Active</p><p class="text-lg font-semibold">{{ data.api_keys?.active ?? 0 }}</p></div>
            <div><p class="text-slate-500">Expired</p><p class="text-lg font-semibold">{{ data.api_keys?.expired ?? 0 }}</p></div>
            <div><p class="text-slate-500">Tokens</p><p class="text-lg font-semibold">{{ data.api_keys?.sanctum_tokens ?? 0 }}</p></div>
          </div>
          <ul class="mt-4 max-h-48 divide-y divide-slate-100 overflow-y-auto">
            <li v-for="key in data.api_keys?.recent || []" :key="key.uuid" class="flex justify-between py-2 text-sm">
              <span class="truncate text-slate-700">{{ key.name }}</span>
              <span class="shrink-0 text-slate-400">{{ key.is_active ? 'active' : 'inactive' }}</span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import SecurityAnalyticsSubnav from '@/modules/analytics/components/SecurityAnalyticsSubnav.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();
const data = computed(() => store.security);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const kpiCards = computed(() => [
  { label: 'Failed logins', value: data.value?.kpis?.logins_failed ?? 0 },
  { label: 'Security events', value: data.value?.kpis?.security_events ?? 0 },
  { label: 'API errors', value: data.value?.kpis?.api_errors ?? 0 },
  { label: 'API key uses', value: data.value?.kpis?.api_key_uses ?? 0 },
  { label: 'GDPR requests', value: data.value?.kpis?.gdpr_requests ?? 0 },
  { label: 'Risk score', value: data.value?.kpis?.risk_score ?? 0 },
]);

const riskColor = computed(() => {
  const level = (data.value?.risk?.level || '').toLowerCase();
  if (level === 'critical' || level === 'high') return 'text-rose-600';
  if (level === 'medium' || level === 'elevated') return 'text-amber-600';
  return 'text-emerald-600';
});

function chartPoints(series = []) {
  return (series || []).map((row) => ({ ...row, label: row.date }));
}

async function load() {
  await store.fetchSecurity({ ...filters });
}

async function onCapture() {
  await store.capture();
  await load();
}

onMounted(load);
</script>
