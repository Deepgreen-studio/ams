<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="onCapture"
      >
        <CameraIcon class="h-4 w-4" :class="{ 'animate-pulse': store.saving }" />
        {{ store.saving ? 'Capturing…' : 'Capture snapshot' }}
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar v-model="filters" :show-category="false" @apply="onApply" @reset="onApply" />

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="Unable to load security dashboard"
        :description="store.error || 'Refresh to try loading failed logins, events, and risk posture again.'"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="load"
          >
            Retry
          </button>
        </template>
      </EmptyState>
    </div>

    <template v-else-if="data">
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="card in kpiCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
            <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Failed logins"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.logins_failed, 'value', 'Failed logins')"
        />
        <SimpleLineChart
          title="Security events"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.security_events, 'value', 'Events')"
        />
        <SimpleLineChart
          title="API errors"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.api_errors, 'value', 'API errors')"
        />
        <SimpleLineChart
          title="Risk score"
          hint="Composite trend"
          v-bind="lineChartProps(data.charts?.risk_score, 'value', 'Risk score')"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Top failed login IPs</h2>
            <p class="mt-0.5 text-xs text-slate-500">Addresses with the most failed authentications.</p>
          </div>
          <div v-if="!(data.failed_login_ips || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No failed logins in range</p>
            <p class="mt-1 text-xs text-slate-500">Failed authentication sources will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="row in data.failed_login_ips"
              :key="row.ip_address"
              class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3"
            >
              <span class="font-mono text-sm text-slate-700">{{ row.ip_address }}</span>
              <span class="text-sm font-medium text-slate-900">{{ formatNumber(row.count) }}</span>
            </li>
          </ul>
        </section>

        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">API keys</h2>
            <p class="mt-0.5 text-xs text-slate-500">Inventory and recent key activity.</p>
          </div>
          <div class="grid grid-cols-2 gap-3 px-6 py-5">
            <div
              v-for="stat in apiKeyStats"
              :key="stat.label"
              class="rounded-[12px] bg-zinc-50 px-4 py-3"
            >
              <p class="text-xs text-slate-500">{{ stat.label }}</p>
              <p class="mt-1 text-lg font-semibold text-slate-900">{{ stat.value }}</p>
            </div>
          </div>
          <div v-if="!(data.api_keys?.recent || []).length" class="px-6 pb-16 text-center">
            <p class="text-sm font-medium text-slate-900">No recent API keys</p>
            <p class="mt-1 text-xs text-slate-500">Newly issued keys will appear here.</p>
          </div>
          <ul v-else class="max-h-48 divide-y divide-zinc-50 overflow-y-auto px-3 pb-2">
            <li
              v-for="key in data.api_keys.recent"
              :key="key.uuid"
              class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3"
            >
              <span class="truncate text-sm text-slate-700">{{ key.name }}</span>
              <span
                class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                :class="key.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-zinc-50 text-slate-600 ring-zinc-200'"
              >
                {{ key.is_active ? 'active' : 'inactive' }}
              </span>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import {
  CameraIcon,
  ChartBarIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  KeyIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';

const store = useSecurityAnalyticsStore();
const toast = useToast();
const data = computed(() => store.security);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const failedLogins = computed(() => Number(data.value?.kpis?.logins_failed ?? 0));
const securityEvents = computed(() => Number(data.value?.kpis?.security_events ?? 0));
const apiErrors = computed(() => Number(data.value?.kpis?.api_errors ?? 0));
const apiKeyUses = computed(() => Number(data.value?.kpis?.api_key_uses ?? 0));
const gdprRequests = computed(() => Number(data.value?.kpis?.gdpr_requests ?? 0));
const riskScore = computed(() => Number(data.value?.kpis?.risk_score ?? data.value?.risk?.score ?? 0));
const riskLevel = computed(() => (data.value?.risk?.level || '').toLowerCase());

const kpiCards = computed(() => [
  {
    label: 'Failed logins',
    value: formatNumber(failedLogins.value),
    hint: 'Authentication failures',
    icon: LockClosedIcon,
    iconBg: failedLogins.value ? 'bg-rose-50' : 'bg-zinc-100',
    iconColor: failedLogins.value ? 'text-rose-500' : 'text-slate-500',
  },
  {
    label: 'Security events',
    value: formatNumber(securityEvents.value),
    hint: 'Recorded in this period',
    icon: ShieldExclamationIcon,
    iconBg: securityEvents.value ? 'bg-amber-50' : 'bg-zinc-100',
    iconColor: securityEvents.value ? 'text-amber-500' : 'text-slate-500',
  },
  {
    label: 'API errors',
    value: formatNumber(apiErrors.value),
    hint: 'Failed API calls',
    icon: ExclamationTriangleIcon,
    iconBg: apiErrors.value ? 'bg-violet-50' : 'bg-zinc-100',
    iconColor: apiErrors.value ? 'text-violet-500' : 'text-slate-500',
  },
  {
    label: 'API key uses',
    value: formatNumber(apiKeyUses.value),
    hint: 'Token activity',
    icon: KeyIcon,
    iconBg: apiKeyUses.value ? 'bg-sky-50' : 'bg-zinc-100',
    iconColor: apiKeyUses.value ? 'text-sky-500' : 'text-slate-500',
  },
  {
    label: 'GDPR requests',
    value: formatNumber(gdprRequests.value),
    hint: 'Privacy intake',
    icon: DocumentTextIcon,
    iconBg: gdprRequests.value ? 'bg-brand-50' : 'bg-zinc-100',
    iconColor: gdprRequests.value ? 'text-brand-500' : 'text-slate-500',
  },
  {
    label: 'Risk score',
    value: formatNumber(riskScore.value),
    hint: data.value?.risk?.level || 'Composite posture',
    icon: ChartBarIcon,
    iconBg: elevatedRisk.value ? 'bg-rose-50' : 'bg-emerald-50',
    iconColor: elevatedRisk.value ? 'text-rose-500' : 'text-emerald-500',
  },
]);

const apiKeyStats = computed(() => [
  { label: 'Total', value: formatNumber(data.value?.api_keys?.total ?? 0) },
  { label: 'Active', value: formatNumber(data.value?.api_keys?.active ?? 0) },
  { label: 'Expired', value: formatNumber(data.value?.api_keys?.expired ?? 0) },
  { label: 'Tokens', value: formatNumber(data.value?.api_keys?.sanctum_tokens ?? 0) },
]);

const elevatedRisk = computed(
  () => riskLevel.value === 'critical' || riskLevel.value === 'high' || riskLevel.value === 'medium' || riskLevel.value === 'elevated',
);

const healthMessage = computed(() => {
  const level = data.value?.risk?.level || 'healthy';
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') {
    return `Risk level is ${level} (score ${riskScore.value}). Investigate failed logins and security events.`;
  }
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') {
    return `Risk level is ${level} (score ${riskScore.value}). Monitor API errors and failed authentications.`;
  }
  return `Security posture is ${level} (score ${riskScore.value}).`;
});

const healthTone = computed(() => {
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') {
    return 'bg-rose-50 text-rose-800 ring-1 ring-rose-100';
  }
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') {
    return ShieldExclamationIcon;
  }
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') {
    return ExclamationTriangleIcon;
  }
  return ShieldCheckIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.security) return;
    toast.error(message);
    store.error = null;
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

function load() {
  store.fetchSecurity({ ...filters }).catch(() => {});
}

async function onCapture() {
  try {
    await store.capture();
    await store.fetchSecurity({ ...filters });
  } catch {
    // Toast is shown from store.error.
  }
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  load();
});
</script>
