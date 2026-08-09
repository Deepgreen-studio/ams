<template>
  <div>
    <!-- <PageHeader
      title="Enterprise Scheduler"
      description="Cron, recurring, one-time, delayed, and queue background jobs."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'scheduler.failed' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Failed jobs
        </RouterLink>
        <RouterLink
          :to="{ name: 'scheduler.jobs.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New job
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'scheduler.failed' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Failed jobs
        </RouterLink>
        <RouterLink
          :to="{ name: 'scheduler.jobs.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New job
        </RouterLink>
    </Teleport>

    <SchedulerSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in jobCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in runCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent runs</h2>
          <RouterLink :to="{ name: 'scheduler.history' }" class="text-xs font-medium text-brand-700 hover:underline">
            View history
          </RouterLink>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-if="!store.recentRuns.length" class="py-6 text-center text-sm text-slate-500">No runs yet.</li>
          <li v-for="run in store.recentRuns" :key="run.uuid" class="flex items-center justify-between gap-3 py-3">
            <div>
              <p class="text-sm font-medium text-slate-900">{{ run.job?.name || 'Job' }}</p>
              <p class="text-xs text-slate-500">{{ formatDate(run.created_at) }}</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(run.status)">
              {{ run.status }}
            </span>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent failures</h2>
          <RouterLink :to="{ name: 'scheduler.failed' }" class="text-xs font-medium text-brand-700 hover:underline">
            View failed
          </RouterLink>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-if="!store.recentFailed.length" class="py-6 text-center text-sm text-slate-500">No failures.</li>
          <li v-for="run in store.recentFailed" :key="run.uuid" class="py-3">
            <p class="text-sm font-medium text-slate-900">{{ run.job?.name || 'Job' }}</p>
            <p class="text-xs text-rose-600">{{ run.error_message || 'Failed' }}</p>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();

const jobCards = computed(() => [
  { label: 'Total jobs', value: store.statistics?.total ?? 0 },
  { label: 'Enabled', value: store.statistics?.enabled ?? 0 },
  { label: 'Cron', value: store.statistics?.cron ?? 0 },
  { label: 'Recurring', value: store.statistics?.recurring ?? 0 },
]);

const runCards = computed(() => [
  { label: 'Total runs', value: store.runStatistics?.total ?? 0 },
  { label: 'Running', value: store.runStatistics?.running ?? 0 },
  { label: 'Success', value: store.runStatistics?.success ?? 0 },
  { label: 'Failed', value: store.runStatistics?.failed ?? 0 },
]);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function statusClass(status) {
  if (status === 'success') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'running') return 'bg-amber-50 text-amber-700';
  return 'bg-slate-100 text-slate-600';
}

onMounted(() => store.fetchDashboard());
</script>
