<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.policies.approvals' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ClipboardDocumentCheckIcon class="h-4 w-4" />
        Approval queue
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.policies.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New policy
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
      <p class="text-sm font-medium text-slate-900">Unable to load policy dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading policy metrics again.</p>
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

      <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Recent policies</h2>
              <p class="mt-0.5 text-xs text-slate-500">Latest drafts, reviews, and published documents</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.policies.index' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View all
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.recent.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.recent.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No policies yet</p>
            <p class="mt-1 text-xs text-slate-500">Create a privacy policy, terms, or internal handbook.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.recent"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.policies.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.title }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">
                  {{ [item.policy_number, item.current_version != null ? `v${item.current_version}` : null].filter(Boolean).join(' · ') }}
                </p>
              </div>
              <PolicyStatusBadge :status="item.status" :label="item.status_label" />
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Pending approvals</h2>
              <p class="mt-0.5 text-xs text-slate-500">Submitted documents waiting for review</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.policies.approvals' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Queue
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.approvalQueuePreview.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.approvalQueuePreview.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No pending reviews</p>
            <p class="mt-1 text-xs text-slate-500">Submitted policies appear here for approval.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.approvalQueuePreview"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  v-if="item.policy?.uuid"
                  :to="{ name: 'compliance.policies.show', params: { id: item.policy.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.policy.title }}
                </RouterLink>
                <p v-else class="text-sm font-medium text-slate-900">Pending review</p>
                <p class="mt-1 text-xs text-slate-500">
                  {{
                    [
                      item.policy?.policy_number,
                      item.version?.version != null ? `v${item.version.version}` : null,
                      item.requester?.full_name,
                    ]
                      .filter(Boolean)
                      .join(' · ')
                  }}
                </p>
              </div>
              <PolicyStatusBadge :status="item.status" :label="item.status_label" />
            </li>
          </ul>
        </section>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">By status</h2>
          <p class="mt-0.5 text-xs text-slate-500">Distribution of all policy documents</p>
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
          <p class="mt-0.5 text-xs text-slate-500">Privacy, terms, security, and internal documents</p>
          <dl class="mt-4 space-y-2.5">
            <div
              v-for="row in typeRows"
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
  ArchiveBoxIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  LinkIcon,
  PlusIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyStatusBadge from '@/modules/compliance/components/PolicyStatusBadge.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';
import { policyStatusLabels, policyTypeLabels } from '@/modules/compliance/utils/policyOptions';

const store = usePolicyStore();
const toast = useToast();
const { can } = usePermissions();

const statistics = computed(() => store.statistics || {});
const hasDashboard = computed(() => Boolean(store.statistics));

const cards = computed(() => {
  const stats = statistics.value;
  const review = stats.review ?? 0;
  const published = stats.published ?? 0;
  const overdue = stats.review_overdue ?? 0;
  const cmsLinked = stats.cms_linked ?? 0;
  const draft = stats.draft ?? 0;
  const approved = stats.approved ?? 0;
  const archived = stats.archived ?? 0;

  return [
    {
      label: 'Total policies',
      value: stats.total ?? 0,
      hint: 'All governed documents',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'In review',
      value: review,
      hint: review ? 'Waiting for approval' : 'No reviews outstanding',
      icon: ClockIcon,
      iconBg: review ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: review ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Published',
      value: published,
      hint: published ? 'Live governed documents' : 'Nothing published yet',
      icon: CheckCircleIcon,
      iconBg: published ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: published ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'CMS linked',
      value: cmsLinked,
      hint: cmsLinked ? 'Synced with content versions' : 'No CMS links',
      icon: LinkIcon,
      iconBg: cmsLinked ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: cmsLinked ? 'text-violet-500' : 'text-slate-500',
    },
    {
      label: 'Draft',
      value: draft,
      hint: draft ? 'Still being authored' : 'No drafts',
      icon: DocumentTextIcon,
      iconBg: draft ? 'bg-zinc-100' : 'bg-zinc-100',
      iconColor: 'text-slate-500',
    },
    {
      label: 'Approved',
      value: approved,
      hint: approved ? 'Ready to publish' : 'Nothing waiting to publish',
      icon: ShieldCheckIcon,
      iconBg: approved ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: approved ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Review overdue',
      value: overdue,
      hint: overdue ? 'Past scheduled review date' : 'All reviews on time',
      icon: ExclamationTriangleIcon,
      iconBg: overdue ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: overdue ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Archived',
      value: archived,
      hint: 'Retired documents',
      icon: ArchiveBoxIcon,
      iconBg: 'bg-zinc-100',
      iconColor: 'text-slate-500',
    },
  ];
});

const healthMessage = computed(() => {
  const stats = statistics.value;
  const overdue = stats.review_overdue ?? 0;
  const review = stats.review ?? 0;
  const approved = stats.approved ?? 0;

  if (overdue > 0) {
    return `${overdue} policy review${overdue === 1 ? '' : 's'} past the due date.`;
  }
  if (review > 0) {
    return `${review} ${review === 1 ? 'policy is' : 'policies are'} waiting in the approval queue.`;
  }
  if (approved > 0) {
    return `${approved} approved polic${approved === 1 ? 'y is' : 'ies are'} ready to publish.`;
  }
  if ((stats.published ?? 0) > 0) {
    return `${stats.published} published polic${stats.published === 1 ? 'y is' : 'ies are'} in force.`;
  }
  return 'Policy register is healthy. No overdue reviews or pending approvals.';
});

const healthTone = computed(() => {
  const stats = statistics.value;
  if ((stats.review_overdue ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if ((stats.review ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  if ((stats.published ?? 0) > 0) return 'bg-emerald-50 text-emerald-800';
  return 'bg-sky-50 text-sky-800';
});

const healthIcon = computed(() => {
  const stats = statistics.value;
  if ((stats.review_overdue ?? 0) > 0) return ExclamationTriangleIcon;
  if ((stats.review ?? 0) > 0) return ClockIcon;
  return ShieldCheckIcon;
});

const statusRows = computed(() =>
  Object.entries(policyStatusLabels).map(([key, label]) => ({
    key,
    label,
    value: Number(statistics.value.by_status?.[key] ?? statistics.value[key] ?? 0),
  })),
);

const typeRows = computed(() =>
  Object.entries(policyTypeLabels).map(([key, label]) => ({
    key,
    label,
    value: Number(statistics.value.by_type?.[key] ?? 0),
  })),
);

async function reload() {
  try {
    await store.fetchDashboard();
  } catch {
    toast.error(store.error || 'Unable to load policy dashboard');
  }
}

onMounted(() => {
  reload();
});
</script>
