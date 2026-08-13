<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.privacy.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <InboxIcon class="h-4 w-4" />
        All requests
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.privacy.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New request
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
      <p class="text-sm font-medium text-slate-900">Unable to load privacy dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading request metrics again.</p>
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
              <h2 class="text-base font-semibold text-slate-900">Recent active requests</h2>
              <p class="mt-0.5 text-xs text-slate-500">Submitted rights requests still in progress</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.privacy.index' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View all
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.recentActive.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.recentActive.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No active requests</p>
            <p class="mt-1 text-xs text-slate-500">Submitted privacy requests will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.recentActive"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.privacy.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.requester_name }}
                </RouterLink>
                <p class="mt-1 text-xs" :class="isOverdue(item) ? 'text-rose-600' : 'text-slate-500'">
                  {{ requestMeta(item) }}
                </p>
              </div>
              <PrivacyStatusBadge :status="item.status" :label="item.status_label" />
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Awaiting verification</h2>
              <p class="mt-0.5 text-xs text-slate-500">Identity checks before fulfilment</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.privacy.index', query: { identity_verification_status: 'pending' } }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Review queue
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.awaitingVerification.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.awaitingVerification.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No pending verifications</p>
            <p class="mt-1 text-xs text-slate-500">Requests waiting for identity checks will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.awaitingVerification"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.privacy.verify', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.requester_name }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">
                  {{ item.request_number }}
                  <span v-if="item.request_type_label"> · {{ item.request_type_label }}</span>
                </p>
              </div>
              <span
                class="shrink-0 text-xs font-medium"
                :class="isOverdue(item) ? 'text-rose-600' : 'text-amber-700'"
              >
                {{ dueLabel(item) }}
              </span>
            </li>
          </ul>
        </section>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">By status</h2>
          <p class="mt-0.5 text-xs text-slate-500">Distribution of all privacy requests</p>
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
          <p class="mt-0.5 text-xs text-slate-500">Volume across GDPR data subject rights</p>
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
          <p v-else class="mt-6 text-sm text-slate-500">No request types recorded yet.</p>
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
  DocumentMagnifyingGlassIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  IdentificationIcon,
  InboxIcon,
  PlusIcon,
  ShieldCheckIcon,
  UserMinusIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyStatusBadge from '@/modules/compliance/components/PrivacyStatusBadge.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';

const store = usePrivacyRequestsStore();
const toast = useToast();
const { can } = usePermissions();

const statistics = computed(() => store.statistics || {});
const hasDashboard = computed(() => Boolean(store.statistics));

const statusLabels = {
  submitted: 'Submitted',
  identity_pending: 'Identity pending',
  under_review: 'Under review',
  approved: 'Approved',
  in_progress: 'In progress',
  completed: 'Completed',
  rejected: 'Rejected',
  cancelled: 'Cancelled',
};

const typeLabels = {
  access_request: 'Access request',
  data_export: 'Data export',
  data_correction: 'Data correction',
  data_deletion: 'Data deletion',
  restrict_processing: 'Restrict processing',
  right_to_object: 'Right to object',
  consent_withdrawal: 'Consent withdrawal',
  data_portability: 'Data portability',
};

const cards = computed(() => {
  const stats = statistics.value;
  const active = stats.active ?? 0;
  const awaiting = stats.awaiting_verification ?? 0;
  const overdue = stats.overdue ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const submitted = stats.submitted ?? 0;
  const underReview = stats.under_review ?? 0;

  return [
    {
      label: 'Total requests',
      value: stats.total ?? 0,
      hint: 'All recorded requests',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Active',
      value: active,
      hint: active ? 'Still in the fulfilment pipeline' : 'No active requests',
      icon: InboxIcon,
      iconBg: active ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: active ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Awaiting verification',
      value: awaiting,
      hint: awaiting ? 'Identity checks outstanding' : 'No pending identity checks',
      icon: IdentificationIcon,
      iconBg: awaiting ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: awaiting ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Submitted',
      value: submitted,
      hint: submitted ? 'Awaiting first review' : 'Nothing in intake',
      icon: ClockIcon,
      iconBg: submitted ? 'bg-indigo-50' : 'bg-zinc-100',
      iconColor: submitted ? 'text-indigo-500' : 'text-slate-500',
    },
    {
      label: 'Under review',
      value: underReview,
      hint: 'Being assessed by the privacy team',
      icon: DocumentMagnifyingGlassIcon,
      iconBg: underReview ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: underReview ? 'text-violet-500' : 'text-slate-500',
    },
    {
      label: 'Overdue',
      value: overdue,
      hint: overdue ? 'Past statutory due date' : 'All active requests on time',
      icon: ExclamationTriangleIcon,
      iconBg: overdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: overdue ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Unassigned',
      value: unassigned,
      hint: unassigned ? 'Needs an owner' : 'All active requests assigned',
      icon: UserMinusIcon,
      iconBg: unassigned ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: unassigned ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Completed',
      value: stats.completed ?? 0,
      hint: 'Fulfilled data subject requests',
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
    },
  ];
});

const healthMessage = computed(() => {
  const stats = statistics.value;
  const overdue = stats.overdue ?? 0;
  const awaiting = stats.awaiting_verification ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const active = stats.active ?? 0;

  if (overdue > 0) {
    return `${overdue} overdue privacy request${overdue === 1 ? '' : 's'} past the due date.`;
  }
  if (awaiting > 0) {
    return `${awaiting} request${awaiting === 1 ? '' : 's'} awaiting identity verification.`;
  }
  if (unassigned > 0) {
    return `${unassigned} unassigned request${unassigned === 1 ? '' : 's'} waiting for an owner.`;
  }
  if (active > 0) {
    return `${active} active privacy request${active === 1 ? '' : 's'} in the fulfilment queue.`;
  }
  return 'Privacy request queue is healthy. No overdue or unverified requests.';
});

const healthTone = computed(() => {
  const stats = statistics.value;
  if ((stats.overdue ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if ((stats.awaiting_verification ?? 0) > 0 || (stats.unassigned ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  if ((stats.active ?? 0) > 0) return 'bg-sky-50 text-sky-800';
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const stats = statistics.value;
  if ((stats.overdue ?? 0) > 0) return ExclamationTriangleIcon;
  if ((stats.awaiting_verification ?? 0) > 0) return IdentificationIcon;
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

  const terminal = ['completed', 'rejected', 'cancelled'];
  if (terminal.includes(item.status)) {
    return false;
  }

  return String(item.due_date) < new Date().toISOString().slice(0, 10);
}

function dueLabel(item) {
  if (!item?.due_date) {
    return 'No due date';
  }

  return isOverdue(item) ? `Overdue ${item.due_date}` : `Due ${item.due_date}`;
}

function requestMeta(item) {
  const parts = [
    item.request_number,
    item.request_type_label || item.request_type,
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
    toast.error(store.error || 'Unable to load privacy dashboard');
  }
}

onMounted(() => {
  reload();
});
</script>
