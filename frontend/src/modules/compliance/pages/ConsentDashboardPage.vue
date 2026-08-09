<template>
  <div>
    <!-- <PageHeader
      title="Consent Management"
      description="Enterprise marketing, analytics, push, email, SMS, and cookie consent overview."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.consents.preferences' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Preference center
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.consents.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Record consent
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'compliance.consents.preferences' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Preference center
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.consents.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Record consent
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
          <h2 class="text-sm font-semibold text-slate-900">Recent consents</h2>
          <RouterLink
            :to="{ name: 'compliance.consents.index' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View all
          </RouterLink>
        </div>
        <EmptyState
          v-if="!store.recent.length && !store.loading"
          title="No consents yet"
          description="Recorded preferences will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in store.recent"
            :key="item.uuid"
            class="flex items-center justify-between py-3"
          >
            <div>
              <RouterLink
                :to="{ name: 'compliance.consents.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.subject_name || item.subject_email }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ item.consent_type?.name }}</p>
            </div>
            <ConsentStatusBadge :status="item.status" :label="item.status_label" />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Consent types</h2>
          <RouterLink
            :to="{ name: 'compliance.consents.audit' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            Audit view
          </RouterLink>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-for="type in store.types" :key="type.uuid" class="py-3">
            <p class="font-medium text-slate-900">{{ type.name }}</p>
            <p class="text-xs text-slate-500">
              Version {{ type.current_version }} · {{ type.channel_label }}
            </p>
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
import ConsentStatusBadge from '@/modules/compliance/components/ConsentStatusBadge.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';

const store = useConsentStore();

const statCards = computed(() => {
  const stats = store.statistics || {};
  return [
    { label: 'Total records', value: stats.total ?? 0 },
    { label: 'Granted', value: stats.granted ?? 0 },
    { label: 'Withdrawn', value: stats.withdrawn ?? 0 },
    { label: 'Pending', value: stats.pending ?? 0 },
  ];
});

onMounted(() => {
  store.fetchDashboard();
});
</script>
