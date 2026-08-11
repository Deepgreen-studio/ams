<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to customer
      </RouterLink>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.refreshing || store.loading"
        @click="refresh"
      >
        {{ store.refreshing ? 'Refreshing…' : 'Refresh snapshot' }}
      </button>
    </Teleport>

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>

    <div
      v-if="store.loading && !current"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <template v-else-if="current">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <MetricCard
          v-for="card in summaryCards"
          :key="card.label"
          :label="card.label"
          :value="card.displayValue"
          :hint="card.hint"
          :trend-label="card.trendLabel"
          :trend-up="card.trendUp"
          :trend-favorable="card.trendFavorable"
          :icon="card.icon"
          :icon-bg="card.iconBg"
          :icon-color="card.iconColor"
        >
          <template v-if="card.kind === 'risk'" #value>
            <RiskLevelBadge :level="card.rawValue" />
          </template>
          <template v-else-if="card.kind === 'subscription'" #value>
            <SubscriptionStatusBadge :status="card.rawValue" />
          </template>
        </MetricCard>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100 lg:col-span-1">
          <h3 class="text-base font-semibold text-slate-900">Customer health</h3>
          <div class="mt-5 flex items-end gap-6">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Health</p>
              <p class="mt-1 text-4xl font-semibold" :class="scoreColor(current.health_score)">
                {{ current.health_score }}
              </p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Activity</p>
              <p class="mt-1 text-3xl font-semibold text-slate-800">{{ current.activity_score }}</p>
            </div>
          </div>
          <div class="mt-5 flex flex-wrap items-center gap-2">
            <RiskLevelBadge :level="current.risk_level" with-suffix />
            <SubscriptionStatusBadge :status="current.subscription_status || 'none'" />
          </div>
          <p class="mt-3 text-xs text-slate-500">
            Subscription
            <span v-if="current.subscription_active" class="font-medium text-emerald-600"
              >· active</span
            >
            <span v-else class="text-slate-400">· inactive</span>
          </p>
        </div>

        <div
          class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 lg:col-span-2"
        >
          <div class="border-b border-zinc-100 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Risk indicators</h3>
          </div>
          <EmptyState
            v-if="!riskIndicators.length"
            title="No risk indicators"
            description="This customer currently has no flagged risk signals."
            class="py-10"
          />
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in riskIndicators"
              :key="item.code"
              class="flex items-start justify-between gap-3 px-5 py-4"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium text-slate-900">{{ item.label }}</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ item.detail }}</p>
              </div>
              <RiskLevelBadge :level="item.severity" />
            </li>
          </ul>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <SimpleLineChart
          title="Health & activity"
          :labels="charts.labels || []"
          :series="scoreSeries"
        />
        <SimpleLineChart
          title="API usage & logins"
          :labels="charts.labels || []"
          :series="usageSeries"
        />
        <SimpleLineChart
          title="Active applications"
          :labels="charts.labels || []"
          :series="[
            { key: 'apps', label: 'Active apps', values: charts.applications_active || [] },
          ]"
        />
        <SimpleLineChart
          title="Support load"
          :labels="charts.labels || []"
          :series="[
            { key: 'support', label: 'Open items', values: charts.support_tickets_open || [] },
          ]"
        />
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <div class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100">
          <h3 class="text-base font-semibold text-slate-900">Usage report</h3>
          <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <div
              v-for="row in usageRows"
              :key="row.label"
              class="grid grid-cols-[1fr_auto] gap-3 px-3.5 py-3"
            >
              <dt class="text-xs font-medium text-slate-500">{{ row.label }}</dt>
              <dd class="text-sm font-medium text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
          <p v-if="usageSourcesNote" class="mt-4 text-xs text-slate-500">{{ usageSourcesNote }}</p>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100">
          <h3 class="text-base font-semibold text-slate-900">Growth trends</h3>
          <div class="mt-5 grid grid-cols-2 gap-3">
            <div
              v-for="row in growthRows"
              :key="row.label"
              class="rounded-[12px] bg-zinc-50 px-4 py-3.5"
            >
              <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ row.label }}</p>
              <p class="mt-1 text-lg font-semibold" :class="deltaClass(row.value)">
                {{ formatDelta(row.value) }}
              </p>
            </div>
          </div>
          <p class="mt-4 text-xs capitalize text-slate-500">
            Direction: {{ growth.direction || 'flat' }}
            <span v-if="store.dashboard?.from">
              · {{ store.dashboard.from }} → {{ store.dashboard.to }}</span
            >
          </p>
        </div>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-5 py-4 sm:px-6 sm:py-5">
          <h3 class="text-base font-semibold text-slate-900">Activity timeline</h3>
        </div>
        <EmptyState
          v-if="!timeline.length"
          title="No timeline events"
          description="Customer notes, tasks, and communications will appear here as activity accumulates."
          class="py-10"
        />
        <div v-else class="px-5 py-5 sm:px-6 sm:py-6">
          <ul class="space-y-4">
            <li
              v-for="item in timeline"
              :key="item.id"
              class="flex items-center gap-4"
            >
              <span
                class="inline-flex h-3 w-3 shrink-0 rounded-full border-2 border-white bg-brand-500 ring-1 ring-brand-200"
              />
              <div class="min-w-0 flex-1 rounded-[12px] bg-zinc-50 px-4 py-3.5 sm:px-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <p class="min-w-0 flex-1 text-sm font-medium text-slate-900">
                    {{ item.description }}
                  </p>
                  <time class="shrink-0 text-xs text-slate-500">{{
                    formatDate(item.created_at)
                  }}</time>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                  {{ formatSubjectType(item.subject_type) }}
                  <span v-if="item.event"> · {{ formatLabel(item.event) }}</span>
                </p>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  BoltIcon,
  CreditCardIcon,
  CpuChipIcon,
  HeartIcon,
  LifebuoyIcon,
  LinkIcon,
  PuzzlePieceIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import MetricCard from '@/components/dashboard/MetricCard.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import RiskLevelBadge from '@/modules/customers/components/RiskLevelBadge.vue';
import SubscriptionStatusBadge from '@/modules/customers/components/SubscriptionStatusBadge.vue';
import { useCustomerAnalyticsStore } from '@/modules/customers/stores/customerAnalytics';

const route = useRoute();
const store = useCustomerAnalyticsStore();

const current = computed(() => store.dashboard?.current ?? null);
const charts = computed(() => store.dashboard?.charts || {});
const growth = computed(() => store.dashboard?.growth || {});
const riskIndicators = computed(() => store.dashboard?.risk_indicators || []);
const timeline = computed(() => store.dashboard?.timeline || []);
const usage = computed(() => store.dashboard?.usage_report || {});

const summaryCards = computed(() => {
  const g = growth.value || {};
  return [
    {
      label: 'Health score',
      displayValue: String(current.value?.health_score ?? 0),
      ...trendFromDelta(g.health_delta),
      icon: HeartIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-600',
    },
    {
      label: 'Activity score',
      displayValue: String(current.value?.activity_score ?? 0),
      ...trendFromDelta(g.activity_delta),
      icon: BoltIcon,
      iconBg: 'bg-violet-50',
      iconColor: 'text-violet-600',
    },
    {
      label: 'Active apps',
      displayValue: String(current.value?.applications_active ?? 0),
      hint: `${current.value?.applications_total ?? 0} total`,
      ...trendFromDelta(g.applications_delta),
      icon: PuzzlePieceIcon,
      iconBg: 'bg-orange-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'API usage',
      displayValue: String(current.value?.api_usage_count ?? 0),
      hint: `${current.value?.login_activity_count ?? 0} logins today`,
      ...trendFromDelta(g.api_usage_delta),
      icon: CpuChipIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-600',
    },
    {
      label: 'Integrations',
      displayValue: String(current.value?.integrations_total ?? 0),
      icon: LinkIcon,
      iconBg: 'bg-indigo-50',
      iconColor: 'text-indigo-600',
    },
    {
      label: 'Support open',
      displayValue: String(current.value?.support_tickets_open ?? 0),
      hint: `${current.value?.support_tickets_total ?? 0} total (proxy)`,
      icon: LifebuoyIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-600',
    },
    {
      label: 'Subscription',
      kind: 'subscription',
      rawValue: current.value?.subscription_status || 'none',
      displayValue: '',
      icon: CreditCardIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-600',
    },
    {
      label: 'Risk',
      kind: 'risk',
      rawValue: current.value?.risk_level || 'low',
      displayValue: '',
      icon: ShieldExclamationIcon,
      iconBg: 'bg-rose-50',
      iconColor: 'text-rose-600',
    },
  ];
});

function trendFromDelta(delta) {
  if (delta === null || delta === undefined) {
    return { trendLabel: '', trendUp: true, trendFavorable: true };
  }
  const n = Number(delta) || 0;
  return {
    trendLabel: n > 0 ? `+${n}` : `${n}`,
    trendUp: n >= 0,
    trendFavorable: n >= 0,
  };
}

const scoreSeries = computed(() => [
  { key: 'health', label: 'Health', values: charts.value.health_score || [] },
  { key: 'activity', label: 'Activity', values: charts.value.activity_score || [] },
]);

const usageSeries = computed(() => [
  { key: 'api', label: 'API usage', values: charts.value.api_usage_count || [] },
  { key: 'login', label: 'Logins', values: charts.value.login_activity_count || [] },
]);

const usageRows = computed(() => [
  { label: 'Applications (active)', value: usage.value.applications_active ?? 0 },
  { label: 'Applications (total)', value: usage.value.applications_total ?? 0 },
  { label: 'Integrations', value: usage.value.integrations_total ?? 0 },
  { label: 'API usage', value: usage.value.api_usage_count ?? 0 },
  { label: 'Login activity', value: usage.value.login_activity_count ?? 0 },
  { label: 'Support open', value: usage.value.support_tickets_open ?? 0 },
  { label: 'Support total', value: usage.value.support_tickets_total ?? 0 },
  { label: 'Documents (current)', value: usage.value.documents_current ?? 0 },
  { label: 'Notes (30d)', value: usage.value.notes_recent ?? 0 },
  { label: 'Communications (30d)', value: usage.value.communications_recent ?? 0 },
]);

const usageSourcesNote = computed(() => {
  const sources = usage.value.sources || {};
  if (!Object.keys(sources).length) return '';
  return `Sources — support: ${sources.support_tickets || 'n/a'}; API: ${sources.api_usage || 'n/a'}; logins: ${sources.login_activity || 'n/a'}.`;
});

const growthRows = computed(() => [
  { label: 'Health Δ', value: growth.value.health_delta ?? 0 },
  { label: 'Activity Δ', value: growth.value.activity_delta ?? 0 },
  { label: 'API usage Δ', value: growth.value.api_usage_delta ?? 0 },
  { label: 'Apps Δ', value: growth.value.applications_delta ?? 0 },
]);

function scoreColor(score) {
  if (score >= 70) return 'text-emerald-600';
  if (score >= 55) return 'text-amber-600';
  return 'text-rose-600';
}

function formatDelta(value) {
  const n = Number(value) || 0;
  return n > 0 ? `+${n}` : `${n}`;
}

function deltaClass(value) {
  const n = Number(value) || 0;
  if (n > 0) return 'text-emerald-600';
  if (n < 0) return 'text-rose-600';
  return 'text-slate-700';
}

function formatDate(value) {
  if (!value) return '—';
  try {
    return new Date(value).toLocaleString();
  } catch {
    return value;
  }
}

function formatLabel(value) {
  return String(value || '')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatSubjectType(value) {
  if (!value) return 'Activity';
  const parts = String(value).split('\\');
  return formatLabel(parts[parts.length - 1] || value);
}

async function refresh() {
  await store.refresh(route.params.id);
}

onMounted(() => store.fetchDashboard(route.params.id));
</script>
