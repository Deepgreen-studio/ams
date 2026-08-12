<template>
  <div>
    <SchedulerSubnav />

    <RunsTable
      :runs="store.runs"
      :loading="store.loading"
      :meta="store.runMeta"
      empty-title="No running jobs"
      empty-description="Currently executing scheduled job runs will appear here."
    >
      <template #footer>
        <Pagination
          v-if="store.runMeta?.total"
          :meta="store.runMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </RunsTable>
  </div>
</template>

<script setup>
import { onMounted, reactive, watch } from 'vue';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import RunsTable from '@/modules/scheduler/components/RunsTable.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
const toast = useToast();
const filters = reactive({ page: 1, per_page: 20 });

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

async function load() {
  await store.fetchRunning({
    page: filters.page,
    per_page: filters.per_page,
  });
}

function onPageChange(page) {
  filters.page = page;
  load();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

onMounted(() => {
  store.error = null;
  load();
});
</script>
