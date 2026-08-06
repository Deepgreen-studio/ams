<template>
  <div>
    <PageHeader
      title="Data Breach Incident Dashboard"
      description="Incident reporting, risk, containment, notifications, and recovery overview."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.breaches.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          All incidents
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.breaches.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Report incident
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
          <h2 class="text-sm font-semibold text-slate-900">Recent active incidents</h2>
          <RouterLink
            :to="{ name: 'compliance.breaches.index' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View all
          </RouterLink>
        </div>
        <EmptyState
          v-if="!store.recentActive.length && !store.loading"
          title="No active incidents"
          description="Reported breaches will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in store.recentActive"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'compliance.breaches.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ item.breach_number }}</p>
            </div>
            <BreachStatusBadge :status="item.status" :label="item.status_label" />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Regulator notification queue</h2>
          <RouterLink
            :to="{ name: 'compliance.breaches.notifications' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            Notification center
          </RouterLink>
        </div>
        <EmptyState
          v-if="!store.regulatorQueue.length && !store.loading"
          title="No pending regulator notices"
          description="High-risk personal data breaches appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in store.regulatorQueue"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'compliance.breaches.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
              <p class="text-xs text-slate-500">
                Deadline {{ formatDate(item.regulator_deadline_at) }}
              </p>
            </div>
            <BreachSeverityBadge :severity="item.severity" :label="item.severity_label" />
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
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import BreachStatusBadge from '@/modules/compliance/components/BreachStatusBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';

const store = useDataBreachStore();

const statCards = computed(() => {
  const s = store.statistics || {};
  return [
    { label: 'Active', value: s.active ?? 0 },
    { label: 'Critical', value: s.critical ?? 0 },
    { label: 'Regulator pending', value: s.regulator_pending ?? 0 },
    { label: 'Affected users', value: s.affected_users_total ?? 0 },
  ];
});

onMounted(() => {
  store.fetchDashboard();
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
