<template>
  <div>
    <PageHeader
      title="Queue Statistics"
      description="Aggregated queue health across priority and functional queues."
    />
    <QueueSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !stats" class="h-64 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="stats">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Connection</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.connection }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jobs table</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ stats.database_jobs_table ?? 0 }}
          </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Failed jobs</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.failed_jobs ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Completed (24h)</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ stats.jobs_last_24h?.completed ?? 0 }}
          </p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            By status
          </h2>
          <dl class="space-y-2 text-sm">
            <div
              v-for="(value, key) in stats.track_status || {}"
              :key="key"
              class="flex justify-between border-b border-slate-100 py-2"
            >
              <dt class="capitalize text-slate-600">{{ key }}</dt>
              <dd class="font-medium text-slate-900">{{ value }}</dd>
            </div>
          </dl>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">By type</h2>
          <dl class="space-y-2 text-sm">
            <div
              v-for="(value, key) in stats.track_types || {}"
              :key="key"
              class="flex justify-between border-b border-slate-100 py-2"
            >
              <dt class="capitalize text-slate-600">{{ key }}</dt>
              <dd class="font-medium text-slate-900">{{ value }}</dd>
            </div>
            <div
              v-if="!Object.keys(stats.track_types || {}).length"
              class="py-6 text-center text-slate-500"
            >
              No tracked jobs yet.
            </div>
          </dl>
        </section>
      </div>

      <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
          Queue depth
        </h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="(size, name) in stats.queue_sizes || {}"
            :key="name"
            class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm"
          >
            <p class="font-medium text-slate-900">{{ name }}</p>
            <p class="text-slate-600">{{ size }}</p>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const stats = computed(() => store.statistics);

onMounted(() => store.fetchStatistics());
</script>
