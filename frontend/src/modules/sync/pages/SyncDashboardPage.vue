<template>
  <div>
    <PageHeader
      title="Sync Dashboard"
      description="Monitor API synchronization runs, progress, and outcomes."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'sync.configs.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New sync config
        </RouterLink>
      </template>
    </PageHeader>
    <SyncSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !totals" class="grid gap-4 md:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-xl bg-slate-100" />
    </div>
    <template v-else>
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

      <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Recent runs
            </h2>
            <RouterLink
              :to="{ name: 'sync.history' }"
              class="text-xs font-medium text-brand-700 hover:underline"
              >View history</RouterLink
            >
          </div>
          <div v-if="!recentRuns.length" class="py-8 text-center text-sm text-slate-500">
            No sync runs yet.
          </div>
          <ul v-else class="divide-y divide-slate-100">
            <li v-for="run in recentRuns" :key="run.uuid" class="py-3">
              <div class="mb-2 flex items-start justify-between gap-3">
                <div>
                  <p class="font-medium text-slate-900">{{ run.config?.name || 'Sync run' }}</p>
                  <p class="text-xs capitalize text-slate-500">
                    {{ run.trigger }} · {{ run.mode }} · {{ run.status }}
                  </p>
                </div>
                <span class="text-xs text-slate-500">{{
                  formatDate(run.started_at || run.created_at)
                }}</span>
              </div>
              <SyncProgressBar :percent="run.progress_percent" :status="run.status" />
              <p class="mt-2 text-xs text-slate-500">
                {{ run.imported }} imported · {{ run.updated }} updated · {{ run.failed }} failed ·
                {{ run.skipped }} skipped
              </p>
            </li>
          </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Configs</h2>
            <RouterLink
              :to="{ name: 'sync.configs' }"
              class="text-xs font-medium text-brand-700 hover:underline"
              >Manage</RouterLink
            >
          </div>
          <div v-if="!configs.length" class="py-8 text-center text-sm text-slate-500">
            No sync configurations.
          </div>
          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="item in configs"
              :key="item.uuid"
              class="flex items-center justify-between gap-3 py-3"
            >
              <div>
                <p class="font-medium text-slate-900">{{ item.name }}</p>
                <p class="text-xs capitalize text-slate-500">
                  {{ item.direction }} · {{ item.trigger_type }} ·
                  {{ item.is_enabled ? 'enabled' : 'disabled' }}
                </p>
              </div>
              <RouterLink
                :to="{ name: 'sync.configs.show', params: { id: item.uuid } }"
                class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
              >
                Open
              </RouterLink>
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
import SyncProgressBar from '@/modules/sync/components/SyncProgressBar.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const store = useSyncStore();

const totals = computed(() => store.dashboard?.totals ?? null);
const recentRuns = computed(() => store.dashboard?.recent_runs ?? []);
const configs = computed(() => store.dashboard?.configs?.items ?? []);

const cards = computed(() => {
  const t = totals.value || {};
  return [
    { label: 'Total runs', value: t.total_runs ?? 0 },
    { label: 'Running', value: t.running ?? 0 },
    { label: 'Completed', value: t.completed ?? 0 },
    { label: 'Failed', value: t.failed ?? 0 },
    { label: 'Imported', value: t.imported ?? 0 },
    { label: 'Updated', value: t.updated ?? 0 },
    { label: 'Skipped', value: t.skipped ?? 0 },
    { label: 'Exported', value: t.exported ?? 0 },
  ];
});

onMounted(() => store.fetchDashboard());

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
