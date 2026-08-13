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

    <div v-if="store.loading && !hasData" class="mb-4 grid gap-4 sm:grid-cols-2">
      <div v-for="n in 2" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasData"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load audit report</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading the activity log again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="hasData">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="card in cards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
          :class="card.span"
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
          title="Audit event volume"
          hint="Daily activity log entries"
          :labels="store.audit.trends?.labels || []"
          :series="[{ key: 'events', label: 'Events', values: store.audit.trends?.events || [] }]"
        />
        <SimpleBarChart title="By event" :data="store.audit.by_event || {}" />
      </div>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4">
          <h2 class="text-base font-semibold text-slate-900">Recent audit results</h2>
          <p class="mt-0.5 text-xs text-slate-500">Latest compliance module activity</p>
        </div>
        <div v-if="!(store.audit.recent || []).length" class="py-10 text-center">
          <p class="text-sm font-medium text-slate-900">No audit events</p>
          <p class="mt-1 text-xs text-slate-500">
            Compliance activity appears here after module actions.
          </p>
        </div>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="item in store.audit.recent"
            :key="item.id"
            class="flex flex-wrap items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900">{{ item.description }}</p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ item.event || 'event' }}
                <span v-if="item.causer?.full_name"> · {{ item.causer.full_name }}</span>
              </p>
            </div>
            <span class="shrink-0 text-xs text-slate-400">{{ formatDate(item.created_at) }}</span>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { CalendarDaysIcon, ClipboardDocumentListIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/compliance/components/AnalyticsFilterBar.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';
import { useComplianceAnalyticsStore } from '@/modules/compliance/stores/complianceAnalytics';

const store = useComplianceAnalyticsStore();
const toast = useToast();

const hasData = computed(() => Boolean(store.audit));

const cards = computed(() => {
  const total = store.audit?.summary?.total ?? 0;
  const from = store.audit?.period?.from || '—';
  const to = store.audit?.period?.to || '—';

  return [
    {
      label: 'Audit events',
      value: total,
      hint: total ? 'Logged in this period' : 'No activity in range',
      icon: ClipboardDocumentListIcon,
      iconBg: total ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: total ? 'text-violet-500' : 'text-slate-500',
    },
    {
      label: 'Period',
      value: `${from} → ${to}`,
      hint: 'Applied report window',
      icon: CalendarDaysIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-500',
      span: 'sm:col-span-2',
    },
  ];
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function reload() {
  try {
    await store.fetchAudit();
  } catch {
    toast.error(store.error || 'Unable to load audit report');
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
    const result = await store.exportReport(format, 'audit');
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
