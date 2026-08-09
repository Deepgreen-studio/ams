<template>
  <div>
    <!-- <PageHeader
      title="Privacy Request Center"
      description="GDPR and privacy request intake, verification, and fulfilment overview."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.privacy.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          All requests
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.privacy.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New request
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'compliance.privacy.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          All requests
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.privacy.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New request
        </RouterLink>
    </Teleport>

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
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent active requests</h2>
          <RouterLink
            :to="{ name: 'compliance.privacy.index' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View all
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recentActive.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <EmptyState
          v-else-if="!store.recentActive.length"
          title="No active requests"
          description="Submitted privacy requests will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in store.recentActive"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'compliance.privacy.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.requester_name }}
              </RouterLink>
              <p class="text-xs text-slate-500">
                {{ item.request_number }} · {{ item.request_type_label }}
              </p>
            </div>
            <PrivacyStatusBadge :status="item.status" :label="item.status_label" />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Awaiting verification</h2>
          <RouterLink
            :to="{ name: 'compliance.privacy.index', query: { identity_verification_status: 'pending' } }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            Review queue
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.awaitingVerification.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <EmptyState
          v-else-if="!store.awaitingVerification.length"
          title="No pending verifications"
          description="Requests waiting for identity checks will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in store.awaitingVerification"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'compliance.privacy.verify', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.requester_name }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ item.request_number }}</p>
            </div>
            <span class="text-xs text-amber-700">Due {{ item.due_date || '—' }}</span>
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
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyStatusBadge from '@/modules/compliance/components/PrivacyStatusBadge.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';

const store = usePrivacyRequestsStore();

const statCards = computed(() => {
  const stats = store.statistics || {};
  return [
    { label: 'Total requests', value: stats.total ?? 0 },
    { label: 'Active', value: stats.active ?? 0 },
    { label: 'Awaiting verification', value: stats.awaiting_verification ?? 0 },
    { label: 'Overdue', value: stats.overdue ?? 0 },
  ];
});

onMounted(() => {
  store.fetchDashboard();
});
</script>
