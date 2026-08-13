<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.policies.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div
      v-if="store.loading && !store.approvalStatistics"
      class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Pending reviews</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Approve or reject submitted policy documents before they can be published.
        </p>
      </div>

      <div v-if="store.loading && !store.approvals.length" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <div v-else-if="!store.approvals.length" class="px-6 py-16 text-center sm:px-8">
        <p class="text-sm font-medium text-slate-900">Queue is empty</p>
        <p class="mt-1 text-xs text-slate-500">Policies submitted for review appear here.</p>
      </div>

      <ul v-else class="divide-y divide-zinc-100">
        <li v-for="item in store.approvals" :key="item.uuid" class="px-6 py-5 sm:px-8">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <RouterLink
                v-if="item.policy?.uuid"
                :to="{ name: 'compliance.policies.show', params: { id: item.policy.uuid } }"
                class="text-sm font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.policy.title }}
              </RouterLink>
              <p v-else class="text-sm font-medium text-slate-900">Pending review</p>
              <p class="mt-1 text-xs text-slate-500">
                {{
                  [
                    item.policy?.policy_number,
                    item.version?.version != null
                      ? `v${item.version.version}`
                      : item.policy?.current_version != null
                        ? `v${item.policy.current_version}`
                        : null,
                    item.requester?.full_name ? `requested by ${item.requester.full_name}` : null,
                  ]
                    .filter(Boolean)
                    .join(' · ')
                }}
              </p>
              <p v-if="item.comments" class="mt-2 text-sm text-slate-600">{{ item.comments }}</p>
              <textarea
                v-if="item.status === 'pending'"
                v-model="comments[item.uuid]"
                rows="2"
                class="input mt-3"
                placeholder="Decision comments (optional)"
                :disabled="store.saving"
              />
            </div>
            <div class="flex shrink-0 flex-col items-end gap-2">
              <PolicyStatusBadge :status="item.status" :label="item.status_label" />
              <template v-if="item.status === 'pending' && can('compliance.update')">
                <button
                  type="button"
                  class="inline-flex h-10 items-center rounded-[12px] bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
                  :disabled="store.saving"
                  @click="onApprove(item)"
                >
                  Approve
                </button>
                <button
                  type="button"
                  class="inline-flex h-10 items-center rounded-[12px] border border-rose-200 px-4 text-sm font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-60"
                  :disabled="store.saving"
                  @click="onReject(item)"
                >
                  Reject
                </button>
              </template>
            </div>
          </div>
        </li>
      </ul>

      <div v-if="store.approvalsMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.approvalsMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  CheckCircleIcon,
  ClockIcon,
  NoSymbolIcon,
  Squares2X2Icon,
  XCircleIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyStatusBadge from '@/modules/compliance/components/PolicyStatusBadge.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = usePolicyStore();
const { can } = usePermissions();
const toast = useToast();
const comments = reactive({});

const cards = computed(() => {
  const stats = store.approvalStatistics || {};
  const pending = stats.pending ?? 0;
  const approved = stats.approved ?? 0;
  const rejected = stats.rejected ?? 0;
  const cancelled = stats.cancelled ?? 0;

  return [
    {
      label: 'Pending',
      value: pending,
      hint: pending ? 'Waiting for a decision' : 'Nothing in queue',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Approved',
      value: approved,
      hint: approved ? 'Ready to publish' : 'No approvals yet',
      icon: CheckCircleIcon,
      iconBg: approved ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: approved ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Rejected',
      value: rejected,
      hint: rejected ? 'Returned to draft' : 'No rejections',
      icon: XCircleIcon,
      iconBg: rejected ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: rejected ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'Cancelled',
      value: cancelled,
      hint: 'Superseded requests',
      icon: NoSymbolIcon,
      iconBg: 'bg-zinc-100',
      iconColor: 'text-slate-500',
    },
  ];
});

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  store.fetchApprovals({ status: 'pending' }).catch(() => {});
});

function onPageChange(page) {
  store.fetchApprovals({ page, status: 'pending' }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchApprovals({ per_page: perPage, page: 1, status: 'pending' }).catch(() => {});
}

async function onApprove(item) {
  try {
    await store.approve(item.uuid, { comments: comments[item.uuid] || undefined });
    toast.success(store.successMessage || 'Policy approved successfully.');
    store.successMessage = null;
    await store.fetchApprovals({ status: 'pending' });
  } catch {
    // Toast is shown from store.error.
  }
}

async function onReject(item) {
  try {
    await store.reject(item.uuid, {
      comments: comments[item.uuid] || 'Rejected from approval queue',
    });
    toast.success(store.successMessage || 'Policy rejected and returned to draft.');
    store.successMessage = null;
    await store.fetchApprovals({ status: 'pending' });
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
