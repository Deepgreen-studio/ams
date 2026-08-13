<template>
  <div>
    <AnalyticsSubnav />

    <EnterpriseFilterBar v-model="filters" :show-category="false" @apply="onApply" @reset="onApply" />

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 7" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load audit dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading logins, permission changes, and deletions again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Retry
      </button>
    </div>

    <template v-else-if="data">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
          title="Successful logins"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.logins_success, 'value', 'Successful')"
        />
        <SimpleLineChart
          title="Failed logins"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.logins_failed, 'value', 'Failed')"
        />
        <SimpleLineChart
          title="Permission changes"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.permission_changes, 'value', 'Permissions')"
        />
        <SimpleLineChart
          title="Role changes"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.role_changes, 'value', 'Roles')"
        />
        <SimpleLineChart
          title="Data exports"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.data_exports, 'value', 'Exports')"
        />
        <SimpleLineChart
          title="Data deletions"
          hint="Daily volume"
          v-bind="lineChartProps(data.charts?.data_deletions, 'value', 'Deletions')"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Recent role / permission events</h2>
            <p class="mt-0.5 text-xs text-slate-500">Access control changes in this period.</p>
          </div>
          <div v-if="!(data.recent_role_events || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No role events</p>
            <p class="mt-1 text-xs text-slate-500">Permission and role changes will appear here.</p>
          </div>
          <ul v-else class="max-h-80 divide-y divide-zinc-50 overflow-y-auto px-3 py-2">
            <li
              v-for="(row, idx) in data.recent_role_events"
              :key="idx"
              class="rounded-[12px] px-3 py-3"
            >
              <p class="text-sm font-medium text-slate-900">{{ row.title || row.event || 'Role event' }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ row.message || row.description }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ row.occurred_at }}</p>
            </li>
          </ul>
        </section>

        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Recent audit actions</h2>
            <p class="mt-0.5 text-xs text-slate-500">Recorded platform audit activity.</p>
          </div>
          <div v-if="!(data.recent_audit_actions || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No audit actions</p>
            <p class="mt-1 text-xs text-slate-500">Exports, deletions, and other audit events will appear here.</p>
          </div>
          <ul v-else class="max-h-80 divide-y divide-zinc-50 overflow-y-auto px-3 py-2">
            <li
              v-for="(row, idx) in data.recent_audit_actions"
              :key="idx"
              class="rounded-[12px] px-3 py-3"
            >
              <p class="text-sm font-medium text-slate-900">{{ row.action || row.title }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ row.module }} · {{ row.message || row.reason }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ row.occurred_at || row.created_at }}</p>
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
  ArrowDownTrayIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  KeyIcon,
  LockClosedIcon,
  TrashIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';
import { lineChartProps } from '@/modules/analytics/utils/chartSeries.js';

const store = useSecurityAnalyticsStore();
const toast = useToast();
const data = computed(() => store.audit);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const kpiCards = computed(() => {
  const kpis = data.value?.kpis || {};
  return [
    kpi('Successful logins', kpis.logins_success, 'Authenticated sessions', CheckCircleIcon, 'emerald'),
    kpi('Failed logins', kpis.logins_failed, 'Authentication failures', LockClosedIcon, 'rose'),
    kpi('Permission changes', kpis.permission_changes, 'Access updates', KeyIcon, 'violet'),
    kpi('Role changes', kpis.role_changes, 'Assignment updates', UserGroupIcon, 'sky'),
    kpi('Data exports', kpis.data_exports, 'Outbound extracts', ArrowDownTrayIcon, 'amber'),
    kpi('Data deletions', kpis.data_deletions, 'Removal events', TrashIcon, 'rose'),
    kpi('Audit actions', kpis.audit_actions, 'Recorded activity', ClipboardDocumentListIcon, 'brand'),
  ];
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.audit) return;
    toast.error(message);
    store.error = null;
  },
);

function kpi(label, value, hint, icon, tone) {
  const count = Number(value || 0);
  const tones = {
    emerald: ['bg-emerald-50', 'text-emerald-500'],
    rose: ['bg-rose-50', 'text-rose-500'],
    violet: ['bg-violet-50', 'text-violet-500'],
    sky: ['bg-sky-50', 'text-sky-500'],
    amber: ['bg-amber-50', 'text-amber-500'],
    brand: ['bg-brand-50', 'text-brand-500'],
  };
  const [iconBg, iconColor] = count ? tones[tone] : ['bg-zinc-100', 'text-slate-500'];

  return {
    label,
    value: formatNumber(count),
    hint,
    icon,
    iconBg,
    iconColor,
  };
}

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

function load() {
  store.fetchAudit({ ...filters }).catch(() => {});
}

onMounted(() => {
  store.error = null;
  load();
});
</script>
