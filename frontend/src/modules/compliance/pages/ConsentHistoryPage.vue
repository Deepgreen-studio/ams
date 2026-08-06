<template>
  <div>
    <PageHeader
      title="Consent history"
      description="Chronological consent grant and withdrawal events."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.consents.audit' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Audit view
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

    <ConsentTimeline :history="store.history" :loading="store.loading" />

    <div class="mt-4">
      <Pagination
        :meta="store.historyMeta"
        :loading="store.loading"
        @change="(page) => store.fetchHistory({ ...historyFilters, page })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import ConsentTimeline from '@/modules/compliance/components/ConsentTimeline.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useConsentStore();
const historyFilters = reactive({
  per_page: 20,
  page: 1,
});

onMounted(() => {
  store.fetchHistory(historyFilters);
});
</script>
