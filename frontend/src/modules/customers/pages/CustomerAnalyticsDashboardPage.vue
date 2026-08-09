<template>
  <div>
    <!-- <PageHeader
      :title="title"
      description="Health score, usage, risk indicators, growth trends, and activity timeline."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to customer
        </RouterLink>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.refreshing || store.loading"
          @click="refresh"
        >
          {{ store.refreshing ? 'Refreshing…' : 'Refresh snapshot' }}
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to customer
        </RouterLink>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
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

    <div v-if="store.loading && !current" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <template v-else-if="current">
      <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-500">{{ card.hint }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-1">
          <h3 class="text-sm font-semibold text-slate-900">Customer health</h3>
          <div class="mt-4 flex items-end gap-6">
            <div>
              <p class="text-xs uppercase tracking-wide text-slate-500">Health</p>
              <p class="text-4xl font-semibold" :class="scoreColor(current.health_score)">
                {{ current.health_score }}
              </p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-slate-500">Activity</p>
              <p class="text-3xl font-semibold text-slate-800">{{ current.activity_score }}</p>
            </div>
          </div>
          <div class="mt-4">
            <span
              class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
              :class="riskBadgeClass(current.risk_level)"
            >
              {{ current.risk_level }} risk
            </span>
            <p class="mt-2 text-xs text-slate-500">
              Subscription: {{ current.subscription_status || 'none' }}
              <span v-if="current.subscription_active" class="text-emerald-600">· active</span>
            </p>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white lg:col-span-2">
          <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">
            Risk indicators
          </div>
          <ul class="divide-y divide-slate-100">
            <li
              v-for="item in riskIndicators"
              :key="item.code"
              class="flex items-start justify-between gap-3 px-4 py-3 text-sm"
            >
              <div>
                <p class="font-medium text-slate-900">{{ item.label }}</p>
                <p class="mt-0.5 text-slate-600">{{ item.detail }}</p>
              </div>
              <span
                class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                :class="riskBadgeClass(item.severity)"
              >
                {{ item.severity }}
              </span>
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
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="text-sm font-semibold text-slate-900">Usage report</h3>
          <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div v-for="row in usageRows" :key="row.label">
              <dt class="text-xs text-slate-500">{{ row.label }}</dt>
              <dd class="font-medium text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
          <p v-if="usageSourcesNote" class="mt-4 text-xs text-slate-500">{{ usageSourcesNote }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="text-sm font-semibold text-slate-900">Growth trends</h3>
          <div class="mt-4 grid grid-cols-2 gap-3">
            <div
              v-for="row in growthRows"
              :key="row.label"
              class="rounded-lg bg-slate-50 px-3 py-3"
            >
              <p class="text-xs uppercase tracking-wide text-slate-500">{{ row.label }}</p>
              <p class="mt-1 text-lg font-semibold" :class="deltaClass(row.value)">
                {{ formatDelta(row.value) }}
              </p>
            </div>
          </div>
          <p class="mt-4 text-xs text-slate-500 capitalize">
            Direction: {{ growth.direction || 'flat' }}
            <span v-if="store.dashboard?.from">
              · {{ store.dashboard.from }} → {{ store.dashboard.to }}</span
            >
          </p>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">
          Activity timeline
        </div>
        <EmptyState
          v-if="!timeline.length"
          title="No timeline events"
          description="Customer notes, tasks, and communications will appear here as activity accumulates."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="item in timeline" :key="item.id" class="px-4 py-3 text-sm">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="font-medium text-slate-900">{{ item.description }}</p>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ item.subject_type || 'Activity' }}
                  <span v-if="item.event"> · {{ item.event }}</span>
                </p>
              </div>
              <time class="shrink-0 text-xs text-slate-500">{{ formatDate(item.created_at) }}</time>
            </div>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import { useCustomerAnalyticsStore } from '@/modules/customers/stores/customerAnalytics';

const route = useRoute();
const store = useCustomerAnalyticsStore();

const current = computed(() => store.dashboard?.current ?? null);
const charts = computed(() => store.dashboard?.charts || {});
const growth = computed(() => store.dashboard?.growth || {});
const riskIndicators = computed(() => store.dashboard?.risk_indicators || []);
const timeline = computed(() => store.dashboard?.timeline || []);
const usage = computed(() => store.dashboard?.usage_report || {});

const title = computed(() => {
  const name = store.dashboard?.customer?.display_name;
  return name ? `${name} analytics` : 'Customer Analytics';
});

const summaryCards = computed(() => [
  { label: 'Health score', value: current.value?.health_score ?? 0 },
  { label: 'Activity score', value: current.value?.activity_score ?? 0 },
  {
    label: 'Active apps',
    value: current.value?.applications_active ?? 0,
    hint: `${current.value?.applications_total ?? 0} total`,
  },
  {
    label: 'API usage',
    value: current.value?.api_usage_count ?? 0,
    hint: `${current.value?.login_activity_count ?? 0} logins today`,
  },
  { label: 'Integrations', value: current.value?.integrations_total ?? 0 },
  {
    label: 'Support open',
    value: current.value?.support_tickets_open ?? 0,
    hint: `${current.value?.support_tickets_total ?? 0} total (proxy)`,
  },
  { label: 'Subscription', value: current.value?.subscription_status || 'none' },
  { label: 'Risk', value: current.value?.risk_level || 'low' },
]);

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

function riskBadgeClass(level) {
  const map = {
    low: 'bg-emerald-50 text-emerald-700',
    medium: 'bg-amber-50 text-amber-800',
    high: 'bg-orange-50 text-orange-800',
    critical: 'bg-rose-50 text-rose-700',
  };
  return map[level] || map.low;
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

async function refresh() {
  await store.refresh(route.params.id);
}

onMounted(() => store.fetchDashboard(route.params.id));
</script>
