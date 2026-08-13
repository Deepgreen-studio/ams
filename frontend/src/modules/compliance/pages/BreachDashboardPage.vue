<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.breaches.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ShieldExclamationIcon class="h-4 w-4" />
        All incidents
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.breaches.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Report incident
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !hasDashboard" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasDashboard"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load breach dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading incident metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else>
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in cards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">
              {{ card.value }}
            </p>
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

      <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Recent active incidents</h2>
              <p class="mt-0.5 text-xs text-slate-500">Open breaches still in assessment or recovery</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.breaches.index' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View all
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.recentActive.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.recentActive.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No active incidents</p>
            <p class="mt-1 text-xs text-slate-500">Reported breaches will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.recentActive"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.breaches.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.title }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">{{ incidentMeta(item) }}</p>
              </div>
              <div class="flex shrink-0 flex-col items-end gap-1.5 sm:flex-row sm:items-center">
                <BreachSeverityBadge :severity="item.severity" :label="item.severity_label" />
                <BreachStatusBadge :status="item.status" :label="item.status_label" />
              </div>
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Regulator queue</h2>
              <p class="mt-0.5 text-xs text-slate-500">Notices still due to a regulator</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.breaches.notifications' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Notification center
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.regulatorQueue.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.regulatorQueue.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No pending regulator notices</p>
            <p class="mt-1 text-xs text-slate-500">High-risk personal data breaches appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.regulatorQueue"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.breaches.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.title }}
                </RouterLink>
                <p
                  class="mt-1 text-xs"
                  :class="isDeadlineOverdue(item) ? 'font-medium text-rose-600' : 'text-slate-500'"
                >
                  {{ deadlineLabel(item) }}
                </p>
              </div>
              <BreachSeverityBadge :severity="item.severity" :label="item.severity_label" />
            </li>
          </ul>
        </section>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">By status</h2>
          <p class="mt-0.5 text-xs text-slate-500">Distribution of all incident records</p>
          <dl class="mt-4 space-y-2.5">
            <div
              v-for="row in statusRows"
              :key="row.key"
              class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
            >
              <dt class="text-sm text-slate-500">{{ row.label }}</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">By severity</h2>
          <p class="mt-0.5 text-xs text-slate-500">Incident volume by assessed severity</p>
          <dl class="mt-4 space-y-2.5">
            <div
              v-for="row in severityRows"
              :key="row.key"
              class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
            >
              <dt class="text-sm text-slate-500">{{ row.label }}</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  BellAlertIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  UserGroupIcon,
  UserMinusIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import BreachStatusBadge from '@/modules/compliance/components/BreachStatusBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';

const store = useDataBreachStore();
const toast = useToast();
const { can } = usePermissions();

const statistics = computed(() => store.statistics || {});
const hasDashboard = computed(() => Boolean(store.statistics));

const statusLabels = {
  reported: 'Reported',
  assessing: 'Assessing',
  contained: 'Contained',
  recovering: 'Recovering',
  notifying: 'Notifying',
  closed: 'Closed',
  cancelled: 'Cancelled',
};

const severityLabels = {
  critical: 'Critical',
  high: 'High',
  medium: 'Medium',
  low: 'Low',
};

const cards = computed(() => {
  const stats = statistics.value;
  const active = stats.active ?? 0;
  const critical = stats.critical ?? 0;
  const regulatorPending = stats.regulator_pending ?? 0;
  const regulatorOverdue = stats.regulator_overdue ?? 0;
  const customerPending = stats.customer_pending ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const affected = stats.affected_users_total ?? 0;

  return [
    {
      label: 'Total incidents',
      value: stats.total ?? 0,
      hint: 'All recorded breach reports',
      icon: ShieldExclamationIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Active',
      value: active,
      hint: active ? 'Still in the incident lifecycle' : 'No active incidents',
      icon: ClockIcon,
      iconBg: active ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: active ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Critical',
      value: critical,
      hint: critical ? 'Needs immediate attention' : 'No critical incidents',
      icon: ExclamationTriangleIcon,
      iconBg: critical ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: critical ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Regulator pending',
      value: regulatorPending,
      hint: regulatorPending ? 'Authority notice still due' : 'No regulator notices waiting',
      icon: BellAlertIcon,
      iconBg: regulatorPending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: regulatorPending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Regulator overdue',
      value: regulatorOverdue,
      hint: regulatorOverdue ? 'Past 72-hour deadline' : 'No overdue regulator notices',
      icon: ExclamationTriangleIcon,
      iconBg: regulatorOverdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: regulatorOverdue ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Customer pending',
      value: customerPending,
      hint: customerPending ? 'Data subject notices outstanding' : 'No customer notices waiting',
      icon: BellAlertIcon,
      iconBg: customerPending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: customerPending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Unassigned',
      value: unassigned,
      hint: unassigned ? 'Needs an incident owner' : 'All active incidents assigned',
      icon: UserMinusIcon,
      iconBg: unassigned ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: unassigned ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Affected users',
      value: affected,
      hint: 'Total people across all incidents',
      icon: UserGroupIcon,
      iconBg: affected ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: affected ? 'text-violet-500' : 'text-slate-500',
    },
  ];
});

const healthMessage = computed(() => {
  const stats = statistics.value;
  const overdue = stats.regulator_overdue ?? 0;
  const critical = stats.critical ?? 0;
  const regulatorPending = stats.regulator_pending ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const active = stats.active ?? 0;

  if (overdue > 0) {
    return `${overdue} regulator notification${overdue === 1 ? '' : 's'} past the statutory deadline.`;
  }
  if (critical > 0) {
    return `${critical} critical incident${critical === 1 ? '' : 's'} need immediate attention.`;
  }
  if (regulatorPending > 0) {
    return `${regulatorPending} regulator notice${regulatorPending === 1 ? '' : 's'} still awaiting dispatch.`;
  }
  if (unassigned > 0) {
    return `${unassigned} unassigned incident${unassigned === 1 ? '' : 's'} waiting for an owner.`;
  }
  if (active > 0) {
    return `${active} active incident${active === 1 ? '' : 's'} in the breach lifecycle.`;
  }
  return 'Breach caseload is healthy. No overdue or unassigned incidents.';
});

const healthTone = computed(() => {
  const stats = statistics.value;
  if ((stats.regulator_overdue ?? 0) > 0 || (stats.critical ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if ((stats.regulator_pending ?? 0) > 0 || (stats.unassigned ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  if ((stats.active ?? 0) > 0) return 'bg-sky-50 text-sky-800';
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const stats = statistics.value;
  if ((stats.regulator_overdue ?? 0) > 0 || (stats.critical ?? 0) > 0) return ExclamationTriangleIcon;
  if ((stats.regulator_pending ?? 0) > 0 || (stats.unassigned ?? 0) > 0 || (stats.active ?? 0) > 0) {
    return ClockIcon;
  }
  return ShieldCheckIcon;
});

const statusRows = computed(() => {
  const byStatus = statistics.value.by_status || {};
  return Object.entries(statusLabels).map(([key, label]) => ({
    key,
    label,
    value: Number(byStatus[key] ?? statistics.value[key] ?? 0),
  }));
});

const severityRows = computed(() => {
  const bySeverity = statistics.value.by_severity || {};
  return Object.entries(severityLabels).map(([key, label]) => ({
    key,
    label,
    value: Number(bySeverity[key] ?? 0),
  }));
});

function incidentMeta(item) {
  const affected = Number(item.affected_user_count ?? 0);
  return [
    item.breach_number,
    item.breach_type_label || item.breach_type,
    item.assignee?.full_name || 'Unassigned',
    affected ? `${affected} affected` : 'No affected users',
  ]
    .filter(Boolean)
    .join(' · ');
}

function isDeadlineOverdue(item) {
  if (!item?.regulator_deadline_at) {
    return false;
  }

  return new Date(item.regulator_deadline_at).getTime() < Date.now();
}

function formatDate(value) {
  if (!value) {
    return '—';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return date.toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
}

function deadlineLabel(item) {
  if (!item?.regulator_deadline_at) {
    return 'No deadline set';
  }

  const formatted = formatDate(item.regulator_deadline_at);
  return isDeadlineOverdue(item) ? `Overdue ${formatted}` : `Deadline ${formatted}`;
}

async function reload() {
  try {
    await store.fetchDashboard();
  } catch {
    toast.error(store.error || 'Unable to load breach dashboard');
  }
}

onMounted(() => {
  reload();
});
</script>
