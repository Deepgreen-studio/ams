<template>
  <div>
    <!-- <PageHeader
      title="DPIA & Risk Dashboard"
      description="Data protection impact assessments, risk register, and mitigation overview."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.dpia.wizard' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Start DPIA wizard
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'compliance.dpia.wizard' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Start DPIA wizard
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

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Recent assessments</h2>
        <EmptyState
          v-if="!store.recentAssessments.length && !store.loading"
          title="No DPIAs yet"
          description="Start a wizard to create an assessment."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="item in store.recentAssessments" :key="item.uuid" class="flex justify-between py-3">
            <RouterLink
              :to="{ name: 'compliance.dpia.show', params: { id: item.uuid } }"
              class="font-medium text-slate-900 hover:text-brand-700"
            >
              {{ item.title }}
            </RouterLink>
            <DpiaStatusBadge :status="item.status" :label="item.status_label" />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Pending approval</h2>
        <EmptyState
          v-if="!store.pendingApproval.length && !store.loading"
          title="No pending reviews"
          description="Submitted DPIAs appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="item in store.pendingApproval" :key="item.uuid" class="py-3">
            <RouterLink
              :to="{ name: 'compliance.dpia.show', params: { id: item.uuid } }"
              class="font-medium text-brand-700 hover:underline"
            >
              {{ item.assessment_number }}
            </RouterLink>
            <p class="text-xs text-slate-500">{{ item.title }}</p>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Mitigation queue</h2>
          <RouterLink
            :to="{ name: 'compliance.dpia.mitigation' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            Tracker
          </RouterLink>
        </div>
        <EmptyState
          v-if="!store.mitigationQueue.length && !store.loading"
          title="No open mitigations"
          description="High-priority risks appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="item in store.mitigationQueue" :key="item.uuid" class="flex justify-between py-3">
            <div>
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">Score {{ item.risk_score }}</p>
            </div>
            <BreachSeverityBadge :severity="item.risk_level" :label="item.risk_level_label" />
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
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import DpiaStatusBadge from '@/modules/compliance/components/DpiaStatusBadge.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const store = useDpiaStore();

const statCards = computed(() => [
  { label: 'Active DPIAs', value: store.dpiaStatistics?.active ?? 0 },
  { label: 'Pending review', value: store.dpiaStatistics?.pending_review ?? 0 },
  { label: 'Open risks', value: store.riskStatistics?.active ?? 0 },
  { label: 'Critical risks', value: store.riskStatistics?.critical ?? 0 },
]);

onMounted(() => store.fetchDashboard());
</script>
