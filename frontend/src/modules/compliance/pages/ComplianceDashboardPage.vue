<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.cases.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <FolderOpenIcon class="h-4 w-4" />
        All cases
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.cases.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create case
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
      <p class="text-sm font-medium text-slate-900">Unable to load compliance dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading case metrics again.</p>
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
              <h2 class="text-base font-semibold text-slate-900">Recent active cases</h2>
              <p class="mt-0.5 text-xs text-slate-500">Open work that still needs an owner or outcome</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.cases.index', query: { status: 'open' } }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View open
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.recentActive.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.recentActive.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No active cases</p>
            <p class="mt-1 text-xs text-slate-500">Active compliance cases will appear here as they are created.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.recentActive"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.cases.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.title }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">{{ caseMeta(item) }}</p>
              </div>
              <div class="flex shrink-0 flex-col items-end gap-1.5 sm:flex-row sm:items-center">
                <CasePriorityBadge :priority="item.priority" :label="item.priority_label" />
                <CaseStatusBadge :status="item.status" :label="item.status_label" />
              </div>
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">High & critical</h2>
              <p class="mt-0.5 text-xs text-slate-500">Elevated priority cases</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.cases.index', query: { priority: 'critical' } }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View elevated
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.elevated.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.elevated.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No elevated cases</p>
            <p class="mt-1 text-xs text-slate-500">High and critical cases will show here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.elevated"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.cases.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.title }}
                </RouterLink>
                <p class="mt-1 text-xs" :class="isOverdue(item) ? 'text-rose-600' : 'text-slate-500'">
                  {{ caseMeta(item) }}
                </p>
              </div>
              <CasePriorityBadge :priority="item.priority" :label="item.priority_label" />
            </li>
          </ul>
        </section>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">By status</h2>
          <p class="mt-0.5 text-xs text-slate-500">Distribution of all compliance cases</p>
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
          <h2 class="text-base font-semibold text-slate-900">By type</h2>
          <p class="mt-0.5 text-xs text-slate-500">Case volume across compliance programs</p>
          <dl v-if="typeRows.length" class="mt-4 space-y-2.5">
            <div
              v-for="row in typeRows"
              :key="row.key"
              class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
            >
              <dt class="text-sm text-slate-500">{{ row.label }}</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
          <p v-else class="mt-6 text-sm text-slate-500">No case types recorded yet.</p>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  FolderOpenIcon,
  InboxIcon,
  PlayCircleIcon,
  PlusIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  UserMinusIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import CasePriorityBadge from '@/modules/compliance/components/CasePriorityBadge.vue';
import CaseStatusBadge from '@/modules/compliance/components/CaseStatusBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';

const store = useComplianceStore();
const toast = useToast();
const { can } = usePermissions();

const statistics = computed(() => store.statistics || {});
const hasDashboard = computed(() => Boolean(store.statistics));

const statusLabels = {
  open: 'Open',
  in_progress: 'In progress',
  under_review: 'Under review',
  pending: 'Pending',
  completed: 'Completed',
  closed: 'Closed',
  cancelled: 'Cancelled',
};

const typeLabels = {
  gdpr: 'GDPR',
  uk_gdpr: 'UK GDPR',
  privacy_request: 'Privacy request',
  compliance_case: 'Compliance case',
  risk_register: 'Risk register',
  audit_compliance: 'Audit compliance',
  iso_27001: 'ISO 27001',
  soc2: 'SOC 2',
  other: 'Other',
};

const cards = computed(() => {
  const stats = statistics.value;
  const active = stats.active ?? 0;
  const overdue = stats.overdue ?? 0;
  const critical = stats.critical ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const open = stats.open ?? 0;
  const inProgress = stats.in_progress ?? 0;

  return [
    {
      label: 'Total cases',
      value: stats.total ?? 0,
      hint: 'All recorded cases',
      icon: FolderOpenIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Active',
      value: active,
      hint: active ? 'Open, in progress, or pending' : 'No active cases',
      icon: InboxIcon,
      iconBg: active ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: active ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Open',
      value: open,
      hint: open ? 'Awaiting first action' : 'Nothing waiting in intake',
      icon: PlayCircleIcon,
      iconBg: open ? 'bg-indigo-50' : 'bg-zinc-100',
      iconColor: open ? 'text-indigo-500' : 'text-slate-500',
    },
    {
      label: 'In progress',
      value: inProgress,
      hint: 'Currently being worked',
      icon: ClockIcon,
      iconBg: inProgress ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: inProgress ? 'text-violet-500' : 'text-slate-500',
    },
    {
      label: 'Overdue',
      value: overdue,
      hint: overdue ? 'Past due date' : 'All active cases on time',
      icon: ExclamationTriangleIcon,
      iconBg: overdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: overdue ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Critical',
      value: critical,
      hint: critical ? 'Needs immediate attention' : 'No critical cases',
      icon: ShieldExclamationIcon,
      iconBg: critical ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: critical ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Unassigned',
      value: unassigned,
      hint: unassigned ? 'Needs an owner' : 'All active cases assigned',
      icon: UserMinusIcon,
      iconBg: unassigned ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: unassigned ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Completed',
      value: stats.completed ?? 0,
      hint: 'Closed with an outcome',
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
    },
  ];
});

const healthMessage = computed(() => {
  const stats = statistics.value;
  const overdue = stats.overdue ?? 0;
  const critical = stats.critical ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const active = stats.active ?? 0;

  if (overdue > 0) {
    return `${overdue} overdue case${overdue === 1 ? '' : 's'} past the due date.`;
  }
  if (critical > 0) {
    return `${critical} critical case${critical === 1 ? '' : 's'} need immediate attention.`;
  }
  if (unassigned > 0) {
    return `${unassigned} unassigned case${unassigned === 1 ? '' : 's'} waiting for an owner.`;
  }
  if (active > 0) {
    return `${active} active case${active === 1 ? '' : 's'} in the compliance queue.`;
  }
  return 'Compliance caseload is healthy. No overdue or unassigned cases.';
});

const healthTone = computed(() => {
  const stats = statistics.value;
  if ((stats.overdue ?? 0) > 0 || (stats.critical ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if ((stats.unassigned ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  if ((stats.active ?? 0) > 0) return 'bg-sky-50 text-sky-800';
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const stats = statistics.value;
  if ((stats.overdue ?? 0) > 0 || (stats.critical ?? 0) > 0) return ExclamationTriangleIcon;
  if ((stats.unassigned ?? 0) > 0 || (stats.active ?? 0) > 0) return ClockIcon;
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

const typeRows = computed(() => {
  const byType = statistics.value.by_type || {};
  return Object.entries(byType)
    .map(([key, value]) => ({
      key,
      label: typeLabels[key] || key.replaceAll('_', ' '),
      value: Number(value ?? 0),
    }))
    .filter((row) => row.value > 0)
    .sort((a, b) => b.value - a.value);
});

function isOverdue(item) {
  if (!item?.due_date) {
    return false;
  }

  const terminal = ['completed', 'closed', 'cancelled'];
  if (terminal.includes(item.status)) {
    return false;
  }

  return String(item.due_date) < new Date().toISOString().slice(0, 10);
}

function caseMeta(item) {
  const parts = [
    item.case_number,
    item.case_type_label || item.case_type,
    item.assignee?.full_name || 'Unassigned',
  ];

  if (item.due_date) {
    parts.push(isOverdue(item) ? `Overdue ${item.due_date}` : `Due ${item.due_date}`);
  }

  return parts.filter(Boolean).join(' · ');
}

async function reload() {
  try {
    await store.fetchDashboard();
  } catch {
    toast.error(store.error || 'Unable to load compliance dashboard');
  }
}

onMounted(() => {
  reload();
});
</script>
