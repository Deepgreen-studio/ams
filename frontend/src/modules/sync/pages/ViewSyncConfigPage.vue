<template>
  <div>
    <!-- <PageHeader
      :title="config?.name || 'Sync config'"
      :description="config?.description || 'Run and inspect this synchronization configuration.'"
    >
      <template #actions>
        <RouterLink
          v-if="config"
          :to="{ name: 'sync.configs.edit', params: { id: config.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          v-if="config"
          :to="{ name: 'sync.configs.edit', params: { id: config.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit
        </RouterLink>
    </Teleport>
    <SyncSubnav />

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

    <div v-if="store.loading && !config" class="h-64 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="config">
      <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Configuration
          </h2>
          <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div>
              <dt class="text-slate-500">Integration</dt>
              <dd class="font-medium text-slate-900">{{ config.integration?.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Company</dt>
              <dd class="font-medium text-slate-900">{{ config.company?.company_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Direction</dt>
              <dd class="capitalize font-medium text-slate-900">{{ config.direction }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Mode</dt>
              <dd class="capitalize font-medium text-slate-900">{{ config.default_mode }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Trigger</dt>
              <dd class="capitalize font-medium text-slate-900">{{ config.trigger_type }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Conflict</dt>
              <dd class="capitalize font-medium text-slate-900">{{ config.conflict_strategy }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Source path</dt>
              <dd class="font-medium text-slate-900">{{ config.source_path || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Target path</dt>
              <dd class="font-medium text-slate-900">{{ config.target_path || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Schedule</dt>
              <dd class="font-medium text-slate-900">{{ config.schedule_cron || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Last synced</dt>
              <dd class="font-medium text-slate-900">{{ formatDate(config.last_synced_at) }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Run sync
          </h2>
          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
                >Mode</label
              >
              <select
                v-model="runForm.mode"
                class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
              >
                <option value="full">Full</option>
                <option value="incremental">Incremental</option>
              </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input
                v-model="runForm.background"
                type="checkbox"
                class="rounded border-slate-300"
              />
              Run in background queue
            </label>
            <button
              type="button"
              class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving || !config.is_enabled"
              @click="run"
            >
              {{ store.saving ? 'Starting...' : 'Run sync now' }}
            </button>
            <p v-if="!config.is_enabled" class="text-xs text-rose-600">
              Enable this config before running.
            </p>
          </div>

          <div v-if="lastRun" class="mt-5 border-t border-slate-100 pt-4">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">
              Latest run
            </p>
            <SyncProgressBar :percent="lastRun.progress_percent" :status="lastRun.status" />
            <p class="mt-2 text-xs capitalize text-slate-600">
              {{ lastRun.status }} · {{ lastRun.mode }} · {{ lastRun.trigger }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
              {{ lastRun.total_records }} total · {{ lastRun.imported }} imported ·
              {{ lastRun.updated }} updated · {{ lastRun.failed }} failed ·
              {{ lastRun.skipped }} skipped
            </p>
            <RouterLink
              :to="{ name: 'sync.logs', query: { sync_run: lastRun.uuid } }"
              class="mt-2 inline-block text-xs font-medium text-brand-700 hover:underline"
            >
              View logs
            </RouterLink>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SyncProgressBar from '@/modules/sync/components/SyncProgressBar.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const route = useRoute();
const store = useSyncStore();
const lastRun = ref(null);
const runForm = reactive({ mode: 'full', background: true });

const config = computed(() => store.currentConfig);

onMounted(async () => {
  const item = await store.fetchConfig(route.params.id);
  runForm.mode = item?.default_mode || 'full';
});

async function run() {
  const result = await store.runSync(route.params.id, {
    mode: runForm.mode,
    background: runForm.background,
  });
  lastRun.value = result?.run ?? null;
  await store.fetchConfig(route.params.id);
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
