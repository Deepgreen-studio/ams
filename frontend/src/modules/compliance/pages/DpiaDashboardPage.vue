<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.dpia.history' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ClockIcon class="h-4 w-4" />
        History
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.dpia.wizard' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Start DPIA wizard
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
      <p class="text-sm font-medium text-slate-900">Unable to load DPIA dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading assessment and risk metrics again.</p>
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
              <h2 class="text-base font-semibold text-slate-900">Recent assessments</h2>
              <p class="mt-0.5 text-xs text-slate-500">Latest DPIAs across draft, review, and approval</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.dpia.history' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View history
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.recentAssessments.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.recentAssessments.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No DPIAs yet</p>
            <p class="mt-1 text-xs text-slate-500">Start a wizard to create an assessment.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.recentAssessments"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.dpia.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.title }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">{{ assessmentMeta(item) }}</p>
              </div>
              <DpiaStatusBadge :status="item.status" :label="item.status_label" />
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-900">Pending approval</h2>
            <p class="mt-0.5 text-xs text-slate-500">Submitted DPIAs waiting for a decision</p>
          </div>
          <div v-if="store.loading && !store.pendingApproval.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.pendingApproval.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No pending reviews</p>
            <p class="mt-1 text-xs text-slate-500">Submitted DPIAs appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.pendingApproval"
              :key="item.uuid"
              class="py-3.5 first:pt-0 last:pb-0"
            >
              <RouterLink
                :to="{ name: 'compliance.dpia.show', params: { id: item.uuid } }"
                class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.assessment_number }}
              </RouterLink>
              <p class="mt-1 text-xs text-slate-500">
                {{ item.title }}
                <span v-if="item.assignee?.full_name"> · {{ item.assignee.full_name }}</span>
              </p>
            </li>
          </ul>
        </section>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Mitigation queue</h2>
              <p class="mt-0.5 text-xs text-slate-500">Highest-scoring open risks</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.dpia.mitigation' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Tracker
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.mitigationQueue.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.mitigationQueue.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No open mitigations</p>
            <p class="mt-1 text-xs text-slate-500">High-priority risks will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.mitigationQueue"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">{{ item.title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ riskMeta(item) }}</p>
              </div>
              <BreachSeverityBadge :severity="item.risk_level" :label="item.risk_level_label" />
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">DPIA by status</h2>
          <p class="mt-0.5 text-xs text-slate-500">Distribution of all assessments</p>
          <dl class="mt-4 space-y-2.5">
            <div
              v-for="row in dpiaStatusRows"
              :key="row.key"
              class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
            >
              <dt class="text-sm text-slate-500">{{ row.label }}</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Risks by level</h2>
              <p class="mt-0.5 text-xs text-slate-500">Open and closed register volume</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.dpia.risk' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Matrix
            </RouterLink>
          </div>
          <dl class="space-y-2.5">
            <div
              v-for="row in riskLevelRows"
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
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import DpiaStatusBadge from '@/modules/compliance/components/DpiaStatusBadge.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const store = useDpiaStore();
const toast = useToast();
const { can } = usePermissions();

const dpiaStats = computed(() => store.dpiaStatistics || {});
const riskStats = computed(() => store.riskStatistics || {});
const hasDashboard = computed(() => Boolean(store.dpiaStatistics || store.riskStatistics));

const dpiaStatusLabels = {
  draft: 'Draft',
  in_progress: 'In progress',
  pending_review: 'Pending review',
  approved: 'Approved',
  rejected: 'Rejected',
  archived: 'Archived',
};

const riskLevelLabels = {
  critical: 'Critical',
  high: 'High',
  medium: 'Medium',
  low: 'Low',
};

const cards = computed(() => {
  const dpia = dpiaStats.value;
  const risk = riskStats.value;
  const active = dpia.active ?? 0;
  const pending = dpia.pending_review ?? 0;
  const reviewOverdue = dpia.review_overdue ?? 0;
  const openRisks = risk.active ?? 0;
  const critical = risk.critical ?? 0;
  const high = risk.high ?? 0;

  return [
    {
      label: 'Total DPIAs',
      value: dpia.total ?? 0,
      hint: 'All recorded assessments',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Active DPIAs',
      value: active,
      hint: active ? 'Draft, in progress, or in review' : 'No active assessments',
      icon: ClipboardDocumentCheckIcon,
      iconBg: active ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: active ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Pending review',
      value: pending,
      hint: pending ? 'Waiting for approval' : 'No reviews outstanding',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'DPIA review overdue',
      value: reviewOverdue,
      hint: reviewOverdue ? 'Past scheduled review date' : 'All reviews on time',
      icon: ExclamationTriangleIcon,
      iconBg: reviewOverdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: reviewOverdue ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Open risks',
      value: openRisks,
      hint: openRisks ? 'On the live risk register' : 'No open risks',
      icon: ShieldExclamationIcon,
      iconBg: openRisks ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: openRisks ? 'text-violet-500' : 'text-slate-500',
    },
    {
      label: 'Critical risks',
      value: critical,
      hint: critical ? 'Needs immediate mitigation' : 'No critical risks',
      icon: ExclamationTriangleIcon,
      iconBg: critical ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: critical ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'High risks',
      value: high,
      hint: high ? 'Elevated residual exposure' : 'No high risks',
      icon: ShieldExclamationIcon,
      iconBg: high ? 'bg-orange-50' : 'bg-zinc-100',
      iconColor: high ? 'text-orange-500' : 'text-slate-500',
    },
    {
      label: 'Approved',
      value: dpia.approved ?? 0,
      hint: 'Assessments signed off',
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
    },
  ];
});

const healthMessage = computed(() => {
  const dpia = dpiaStats.value;
  const risk = riskStats.value;
  const reviewOverdue = dpia.review_overdue ?? 0;
  const riskOverdue = risk.review_overdue ?? 0;
  const critical = risk.critical ?? 0;
  const pending = dpia.pending_review ?? 0;
  const openRisks = risk.active ?? 0;
  const active = dpia.active ?? 0;

  if (reviewOverdue > 0) {
    return `${reviewOverdue} DPIA review${reviewOverdue === 1 ? '' : 's'} past the due date.`;
  }
  if (riskOverdue > 0) {
    return `${riskOverdue} risk review${riskOverdue === 1 ? '' : 's'} overdue on the register.`;
  }
  if (critical > 0) {
    return `${critical} critical risk${critical === 1 ? '' : 's'} need immediate mitigation.`;
  }
  if (pending > 0) {
    return `${pending} DPIA${pending === 1 ? '' : 's'} waiting for approval.`;
  }
  if (openRisks > 0 || active > 0) {
    return `${active} active assessment${active === 1 ? '' : 's'} and ${openRisks} open risk${openRisks === 1 ? '' : 's'} on the register.`;
  }
  return 'DPIA and risk register are healthy. No overdue reviews or critical risks.';
});

const healthTone = computed(() => {
  const dpia = dpiaStats.value;
  const risk = riskStats.value;
  if ((dpia.review_overdue ?? 0) > 0 || (risk.review_overdue ?? 0) > 0 || (risk.critical ?? 0) > 0) {
    return 'bg-rose-50 text-rose-800';
  }
  if ((dpia.pending_review ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  if ((dpia.active ?? 0) > 0 || (risk.active ?? 0) > 0) return 'bg-sky-50 text-sky-800';
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const dpia = dpiaStats.value;
  const risk = riskStats.value;
  if ((dpia.review_overdue ?? 0) > 0 || (risk.review_overdue ?? 0) > 0 || (risk.critical ?? 0) > 0) {
    return ExclamationTriangleIcon;
  }
  if ((dpia.pending_review ?? 0) > 0 || (dpia.active ?? 0) > 0 || (risk.active ?? 0) > 0) {
    return ClockIcon;
  }
  return ShieldCheckIcon;
});

const dpiaStatusRows = computed(() => {
  const byStatus = dpiaStats.value.by_status || {};
  return Object.entries(dpiaStatusLabels).map(([key, label]) => ({
    key,
    label,
    value: Number(byStatus[key] ?? dpiaStats.value[key] ?? 0),
  }));
});

const riskLevelRows = computed(() => {
  const byLevel = riskStats.value.by_level || {};
  return Object.entries(riskLevelLabels).map(([key, label]) => ({
    key,
    label,
    value: Number(byLevel[key] ?? riskStats.value[key] ?? 0),
  }));
});

function assessmentMeta(item) {
  return [
    item.assessment_number,
    item.template_label || item.template_code,
    item.assignee?.full_name || 'Unassigned',
  ]
    .filter(Boolean)
    .join(' · ');
}

function riskMeta(item) {
  return [
    item.risk_number,
    item.risk_score != null ? `Score ${item.risk_score}` : null,
    item.owner?.full_name || 'Unassigned',
  ]
    .filter(Boolean)
    .join(' · ');
}

async function reload() {
  try {
    await store.fetchDashboard();
  } catch {
    toast.error(store.error || 'Unable to load DPIA dashboard');
  }
}

onMounted(() => {
  reload();
});
</script>
