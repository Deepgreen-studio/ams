<template>
  <div>
    <PageHeader
      title="Queue Dashboard"
      description="Monitor Laravel queue workers, running jobs, failures, and priorities."
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="store.saving"
          @click="onRestart"
        >
          Restart workers
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="onSample"
        >
          Dispatch sample
        </button>
      </template>
    </PageHeader>
    <QueueSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !dash" class="grid gap-4 md:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-xl bg-slate-100" />
    </div>
    <template v-else-if="dash">
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in cards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
          Queue sizes
        </h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="(size, name) in dash.queue_sizes || {}"
            :key="name"
            class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm"
          >
            <p class="font-medium text-slate-900">{{ name }}</p>
            <p class="text-slate-600">{{ size }} waiting</p>
          </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">
          Worker suggestion:
          <code class="rounded bg-slate-100 px-1 py-0.5"
            >php artisan queue:work --queue={{ (dash.worker_queues || []).join(',') }}</code
          >
        </p>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Recent tracks
            </h2>
            <RouterLink
              :to="{ name: 'queue.running' }"
              class="text-xs font-medium text-brand-700 hover:underline"
              >Running jobs</RouterLink
            >
          </div>
          <div
            v-if="!(dash.recent_tracks || []).length"
            class="py-8 text-center text-sm text-slate-500"
          >
            No tracked jobs yet.
          </div>
          <ul v-else class="divide-y divide-slate-100 text-sm">
            <li
              v-for="item in dash.recent_tracks"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3"
            >
              <div>
                <p class="font-medium text-slate-900">{{ item.display_name || item.job_class }}</p>
                <p class="text-xs capitalize text-slate-500">
                  {{ item.type }} · {{ item.queue }} · {{ item.status }}
                </p>
              </div>
              <span class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</span>
            </li>
          </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Recent failures
            </h2>
            <RouterLink
              :to="{ name: 'queue.failed' }"
              class="text-xs font-medium text-brand-700 hover:underline"
              >Manage failed</RouterLink
            >
          </div>
          <div
            v-if="!(dash.recent_failed || []).length"
            class="py-8 text-center text-sm text-slate-500"
          >
            No failed jobs.
          </div>
          <ul v-else class="divide-y divide-slate-100 text-sm">
            <li v-for="item in dash.recent_failed" :key="item.uuid" class="py-3">
              <div class="mb-1 flex items-start justify-between gap-3">
                <p class="font-medium text-slate-900">{{ item.display_name }}</p>
                <span class="text-xs text-slate-500">{{ formatDate(item.failed_at) }}</span>
              </div>
              <p class="text-xs text-rose-600">{{ item.exception }}</p>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import QueueSubnav from '@/modules/queue/components/QueueSubnav.vue';
import { useQueueStore } from '@/modules/queue/stores/queue';

const store = useQueueStore();
const dash = computed(() => store.dashboard);

const cards = computed(() => {
  const d = dash.value || {};
  return [
    { label: 'Connection', value: d.connection || '—' },
    { label: 'Pending', value: d.pending?.pending ?? 0 },
    { label: 'Running', value: d.tracks?.running ?? d.pending?.running ?? 0 },
    { label: 'Failed', value: d.failed_count ?? 0 },
    { label: 'Tracked completed', value: d.tracks?.completed ?? 0 },
    { label: 'Tracked failed', value: d.tracks?.failed ?? 0 },
    { label: 'Imports', value: d.by_type?.import ?? 0 },
    { label: 'Webhooks', value: d.by_type?.webhook ?? 0 },
  ];
});

onMounted(() => store.fetchDashboard());

async function onRestart() {
  await store.restartWorkers();
  await store.fetchDashboard();
}

async function onSample() {
  await store.dispatchSample({ channel: 'in_app', priority: 'normal', delay_seconds: 0 });
  await store.fetchDashboard();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
