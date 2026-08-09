<template>
  <div>
    <!-- <PageHeader
      title="Policy approval queue"
      description="Review submitted policy documents before publish."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.policies.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'compliance.policies.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.approvals.length"
        title="Queue is empty"
        description="Policies submitted for review appear here."
      />
      <ul v-else class="divide-y divide-slate-100">
        <li v-for="item in store.approvals" :key="item.uuid" class="px-4 py-4 md:px-6">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <RouterLink
                v-if="item.policy?.uuid"
                :to="{ name: 'compliance.policies.show', params: { id: item.policy.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.policy.title }}
              </RouterLink>
              <p class="mt-1 text-xs text-slate-500">
                {{ item.policy?.policy_number }} · v{{ item.version?.version || item.policy?.current_version }}
                <span v-if="item.requester?.full_name"> · requested by {{ item.requester.full_name }}</span>
              </p>
              <p v-if="item.comments" class="mt-2 text-sm text-slate-600">{{ item.comments }}</p>
              <textarea
                v-model="comments[item.uuid]"
                rows="2"
                class="input mt-3"
                placeholder="Decision comments"
              />
            </div>
            <div class="flex flex-col gap-2">
              <PolicyStatusBadge :status="item.status" :label="item.status_label" />
              <button
                type="button"
                class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
                :disabled="store.saving || item.status !== 'pending'"
                @click="onApprove(item)"
              >
                Approve
              </button>
              <button
                type="button"
                class="rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 disabled:opacity-60"
                :disabled="store.saving || item.status !== 'pending'"
                @click="onReject(item)"
              >
                Reject
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <div class="mt-4">
      <Pagination
        :meta="store.approvalsMeta"
        :loading="store.loading"
        @change="(page) => store.fetchApprovals({ page, status: 'pending' })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyStatusBadge from '@/modules/compliance/components/PolicyStatusBadge.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = usePolicyStore();
const comments = reactive({});

onMounted(() => store.fetchApprovals({ status: 'pending' }));

async function onApprove(item) {
  await store.approve(item.uuid, { comments: comments[item.uuid] || undefined });
  await store.fetchApprovals({ status: 'pending' });
}

async function onReject(item) {
  await store.reject(item.uuid, {
    comments: comments[item.uuid] || 'Rejected from approval queue',
  });
  await store.fetchApprovals({ status: 'pending' });
}
</script>
