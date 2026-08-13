<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.analytics.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Overview
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <AnalyticsFilterBar
      :model-value="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @reset="onReset"
      @export="onExport"
    />

    <div v-if="store.loading && !hasData" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasData"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load risk charts</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading risk metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="hasData">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
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
          title="Risk intake vs closures"
          hint="Opened and closed in period"
          :labels="store.risks.trends?.labels || []"
          :series="trendSeries"
        />
        <SimpleBarChart title="By risk level" :data="store.risks.by_level || {}" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <SimpleBarChart title="By status" :data="store.risks.by_status || {}" />
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-900">Top open risks</h2>
            <p class="mt-0.5 text-xs text-slate-500">Highest-scoring active items on the register</p>
          </div>
          <div v-if="!(store.risks.top_risks || []).length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No active risks</p>
            <p class="mt-1 text-xs text-slate-500">High-scoring risks appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.risks.top_risks"
              :key="item.uuid"
              class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">{{ item.title }}</p>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ [item.risk_number, item.status_label || item.status].filter(Boolean).join(' · ') }}
                </p>
              </div>
              <span class="shrink-0 text-sm font-semibold text-slate-900">{{ item.risk_score }}</span>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ChartBarIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();
const toast = useToast();

const hasData = computed(() => Boolean(store.risks));

const summaryCards = computed(() => {
  const s = store.risks?.summary || {};
  const open = s.open ?? 0;
  const closed = s.closed ?? 0;
  const created = s.created_in_period ?? 0;

  return [
    {
      label: 'Open risks',
      value: open,
      hint: open ? 'Active on the register' : 'Nothing open',
      icon: ExclamationTriangleIcon,
      iconBg: open ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: open ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Closed risks',
      value: closed,
      hint: closed ? 'Mitigated or accepted' : 'None closed in view',
      icon: CheckCircleIcon,
      iconBg: closed ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: closed ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Average score',
      value: s.average_score ?? 0,
      hint: 'Across risks in this period',
      icon: ChartBarIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-500',
    },
    {
      label: 'Created in period',
      value: created,
      hint: created ? 'New risks registered' : 'No new risks',
      icon: PlusIcon,
      iconBg: created ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: created ? 'text-brand-500' : 'text-slate-500',
    },
  ];
});

const trendSeries = computed(() => [
  { key: 'opened', label: 'Opened', values: store.risks?.trends?.opened || [] },
  { key: 'closed', label: 'Closed', values: store.risks?.trends?.closed || [] },
]);

async function reload() {
  try {
    await store.fetchRisks();
  } catch {
    toast.error(store.error || 'Unable to load risk charts');
    store.error = null;
  }
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  reload();
}

function onReset() {
  store.resetFilters();
  reload();
}

async function onExport(format) {
  try {
    const result = await store.exportReport(format, 'risks');
    if (result === 'pdf-ready') {
      toast.info(store.successMessage || 'PDF export is architecture-ready.');
    } else {
      toast.success(store.successMessage || 'Export downloaded.');
    }
    store.successMessage = null;
  } catch {
    toast.error(store.error || 'Unable to export analytics');
    store.error = null;
  }
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  reload();
});
</script>
