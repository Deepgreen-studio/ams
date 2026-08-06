<template>
  <div>
    <PageHeader title="Data breaches" description="Search and manage reported breach incidents.">
      <template #actions>
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

    <BreachSearchFilters
      :filters="store.filters"
      @search="(filters) => store.fetchBreaches({ ...filters, page: 1 })"
      @reset="(filters) => store.fetchBreaches({ ...filters, page: 1 })"
    />

    <BreachTable :breaches="store.breaches" :loading="store.loading">
      <template #empty-action>
        <RouterLink
          :to="{ name: 'compliance.breaches.create' }"
          class="mt-3 inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white"
        >
          Report incident
        </RouterLink>
      </template>
    </BreachTable>

    <div class="mt-4">
      <Pagination
        :meta="store.meta"
        :loading="store.loading"
        @change="(page) => store.fetchBreaches({ page })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import BreachSearchFilters from '@/modules/compliance/components/BreachSearchFilters.vue';
import BreachTable from '@/modules/compliance/components/BreachTable.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useDataBreachStore();

onMounted(() => {
  store.fetchBreaches();
});
</script>
