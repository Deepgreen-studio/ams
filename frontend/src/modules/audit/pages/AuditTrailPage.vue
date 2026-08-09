<template>
  <div>
    <!-- <PageHeader title="Audit trail" description="Structured before/after change history across modules." /> -->
    <AuditTabs />
    <div class="mt-4 space-y-4">
      <SearchFilters :model-value="store.filters" @submit="store.fetchList" @reset="onReset" />
      <AuditTable :items="store.items" :loading="store.loading" @select="selected = $event" />
      <Pagination :meta="store.meta" :loading="store.loading" @change="(page) => store.fetchList({ page })" />
    </div>
    <LogDetailsModal :open="Boolean(selected)" :item="selected" title="Audit details" :subtitle="selected?.module || ''" @close="selected = null" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import AuditTable from '@/modules/audit/components/AuditTable.vue';
import AuditTabs from '@/modules/audit/components/AuditTabs.vue';
import LogDetailsModal from '@/modules/audit/components/LogDetailsModal.vue';
import SearchFilters from '@/modules/audit/components/SearchFilters.vue';
import { useAuditStore } from '@/modules/audit/stores/audit';

const store = useAuditStore();
const selected = ref(null);
onMounted(() => store.fetchList());
function onReset() {
  store.filters = { search: '', module: '', action: '', date_from: '', date_to: '', per_page: 15, page: 1 };
  store.fetchList();
}
</script>
