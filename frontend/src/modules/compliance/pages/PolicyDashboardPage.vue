<template>
  <div>
    <PageHeader
      title="Policy Dashboard"
      description="Govern privacy, terms, security, and internal policy documents with immutable versioning."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.policies.approvals' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Approval queue
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.policies.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New policy
        </RouterLink>
      </template>
    </PageHeader>

    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent policies</h2>
          <RouterLink
            :to="{ name: 'compliance.policies.index' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View all
          </RouterLink>
        </div>
        <EmptyState
          v-if="!store.recent.length && !store.loading"
          title="No policies yet"
          description="Create a privacy policy, terms, or internal handbook."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="item in store.recent" :key="item.uuid" class="flex items-center justify-between gap-3 py-3">
            <RouterLink
              :to="{ name: 'compliance.policies.show', params: { id: item.uuid } }"
              class="min-w-0 font-medium text-slate-900 hover:text-brand-700"
            >
              <span class="block truncate">{{ item.title }}</span>
              <span class="block text-xs font-normal text-slate-500">
                {{ item.policy_number }} · v{{ item.current_version }}
              </span>
            </RouterLink>
            <PolicyStatusBadge :status="item.status" :label="item.status_label" />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Pending approvals</h2>
          <RouterLink
            :to="{ name: 'compliance.policies.approvals' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            Queue
          </RouterLink>
        </div>
        <EmptyState
          v-if="!store.approvalQueuePreview.length && !store.loading"
          title="No pending reviews"
          description="Submitted policies appear here for approval."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="item in store.approvalQueuePreview" :key="item.uuid" class="py-3">
            <RouterLink
              v-if="item.policy?.uuid"
              :to="{ name: 'compliance.policies.show', params: { id: item.policy.uuid } }"
              class="font-medium text-brand-700 hover:underline"
            >
              {{ item.policy.policy_number }}
            </RouterLink>
            <p class="text-xs text-slate-500">{{ item.policy?.title }}</p>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyStatusBadge from '@/modules/compliance/components/PolicyStatusBadge.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';

const store = usePolicyStore();

const statCards = computed(() => [
  { label: 'Total policies', value: store.statistics?.total ?? 0 },
  { label: 'In review', value: store.statistics?.review ?? 0 },
  { label: 'Published', value: store.statistics?.published ?? 0 },
  { label: 'CMS linked', value: store.statistics?.cms_linked ?? 0 },
]);

onMounted(() => store.fetchDashboard());
</script>
