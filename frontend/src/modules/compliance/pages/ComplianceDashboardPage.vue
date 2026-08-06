<template>
  <div>
    <PageHeader
      title="Compliance Center"
      description="Enterprise privacy, governance, and compliance case overview."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.cases.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          All cases
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.cases.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create case
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
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent active cases</h2>
          <RouterLink
            :to="{ name: 'compliance.cases.index', query: { status: 'open' } }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View open
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.recentActive.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <EmptyState
          v-else-if="!store.recentActive.length"
          title="No active cases"
          description="Active compliance cases will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in store.recentActive"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'compliance.cases.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ item.case_number }}</p>
            </div>
            <CaseStatusBadge :status="item.status" :label="item.status_label" />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">High & critical</h2>
          <RouterLink
            :to="{ name: 'compliance.cases.index', query: { priority: 'critical' } }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View elevated
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.elevated.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <EmptyState
          v-else-if="!store.elevated.length"
          title="No elevated cases"
          description="High and critical priority cases will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in store.elevated"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'compliance.cases.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ item.case_number }}</p>
            </div>
            <CasePriorityBadge :priority="item.priority" :label="item.priority_label" />
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
import CasePriorityBadge from '@/modules/compliance/components/CasePriorityBadge.vue';
import CaseStatusBadge from '@/modules/compliance/components/CaseStatusBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';

const store = useComplianceStore();

const statCards = computed(() => {
  const stats = store.statistics || {};
  return [
    { label: 'Total cases', value: stats.total ?? 0 },
    { label: 'Active', value: stats.active ?? 0 },
    { label: 'Overdue', value: stats.overdue ?? 0 },
    { label: 'Critical', value: stats.critical ?? 0 },
  ];
});

onMounted(() => {
  store.fetchDashboard();
});
</script>
