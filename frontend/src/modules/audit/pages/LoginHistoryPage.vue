<template>
  <div>
    <AuditTabs />

    <div class="space-y-4">
      <SearchFilters
        :model-value="store.filters"
        :show-module="false"
        :show-action="false"
        @submit="store.fetchList"
        @reset="onReset"
      />
      <LoginHistoryTable :items="store.items" :loading="store.loading" />
      <Pagination
        :meta="store.meta"
        :loading="store.loading"
        @change="(page) => store.fetchList({ page })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import AuditTabs from '@/modules/audit/components/AuditTabs.vue';
import LoginHistoryTable from '@/modules/audit/components/LoginHistoryTable.vue';
import SearchFilters from '@/modules/audit/components/SearchFilters.vue';
import { useLoginHistoryStore } from '@/modules/audit/stores/audit';

const store = useLoginHistoryStore();

onMounted(() => store.fetchList());

function onReset() {
  store.filters = {
    search: '',
    date_from: '',
    date_to: '',
    status: '',
    per_page: 15,
    page: 1,
  };
  store.fetchList();
}
</script>
