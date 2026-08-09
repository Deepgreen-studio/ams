<template>
  <div>
    <!-- <PageHeader title="Failed Jobs" description="Failed scheduled runs with retry actions." /> -->
    <SchedulerSubnav />
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <RunsTable :runs="store.runs" :loading="store.loading" :show-retry="true" @retry="onRetry" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import RunsTable from '@/modules/scheduler/components/RunsTable.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();

async function onRetry(run) {
  await store.retryRun(run.uuid);
}

onMounted(() => store.fetchFailed());
</script>
