<template>
  <div>
    <!-- <PageHeader title="Running Jobs" description="Currently executing scheduled job runs." /> -->
    <SchedulerSubnav />
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <RunsTable :runs="store.runs" :loading="store.loading" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import RunsTable from '@/modules/scheduler/components/RunsTable.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
onMounted(() => store.fetchRunning());
</script>
