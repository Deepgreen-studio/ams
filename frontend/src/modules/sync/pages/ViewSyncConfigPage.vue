<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="config"
        :to="{ name: 'sync.configs.edit', params: { id: config.uuid } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Edit
      </RouterLink>
    </Teleport>

    <SyncSubnav />

    <div
      v-if="store.loading && !config"
      class="h-64 animate-pulse rounded-[12px] bg-zinc-100"
    />

    <template v-else-if="config">
      <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2">
          <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Configuration</h2>
              <p class="mt-0.5 text-sm text-slate-500">
                {{ config.description || 'Run and inspect this synchronization configuration.' }}
              </p>
            </div>
            <span
              class="rounded-full px-2.5 py-1 text-xs font-medium"
              :class="
                config.is_enabled
                  ? 'bg-emerald-50 text-emerald-700'
                  : 'bg-zinc-100 text-slate-500'
              "
            >
              {{ config.is_enabled ? 'Enabled' : 'Disabled' }}
            </span>
          </div>
          <dl class="grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Integration</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ config.integration?.name || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Company</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ config.company?.company_name || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Direction</dt>
              <dd class="mt-1 text-sm font-medium capitalize text-slate-900">{{ config.direction }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Mode</dt>
              <dd class="mt-1 text-sm font-medium capitalize text-slate-900">{{ config.default_mode }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Trigger</dt>
              <dd class="mt-1 text-sm font-medium capitalize text-slate-900">{{ config.trigger_type }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Conflict</dt>
              <dd class="mt-1 text-sm font-medium capitalize text-slate-900">
                {{ config.conflict_strategy }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Source path</dt>
              <dd class="mt-1 font-mono text-sm text-slate-900">{{ config.source_path || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Target path</dt>
              <dd class="mt-1 font-mono text-sm text-slate-900">{{ config.target_path || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Schedule</dt>
              <dd class="mt-1 font-mono text-sm text-slate-900">{{ config.schedule_cron || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Last synced</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ formatDate(config.last_synced_at) }}
              </dd>
            </div>
          </dl>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">Run sync</h2>
          <p class="mt-0.5 text-sm text-slate-500">Start a full or incremental run now.</p>
          <div class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Mode</label>
              <SelectBox v-model="runForm.mode" size="lg" :options="modeOptions" />
            </div>
            <label
              class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-zinc-50 px-4 py-3"
            >
              <span>
                <span class="block text-sm font-medium text-slate-900">Background queue</span>
                <span class="mt-0.5 block text-xs text-slate-500">Run asynchronously via workers.</span>
              </span>
              <input
                v-model="runForm.background"
                type="checkbox"
                class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
              />
            </label>
            <button
              type="button"
              class="w-full rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving || !config.is_enabled"
              @click="run"
            >
              {{ store.saving ? 'Starting...' : 'Run sync now' }}
            </button>
            <p v-if="!config.is_enabled" class="text-xs text-rose-600">
              Enable this config before running.
            </p>
          </div>

          <div v-if="lastRun" class="mt-5 border-t border-zinc-100 pt-5">
            <p class="mb-3 text-sm font-medium text-slate-900">Latest run</p>
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
              class="mt-3 inline-block text-xs font-medium text-brand-700 hover:underline"
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useToast } from '@/composables/useToast';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SyncProgressBar from '@/modules/sync/components/SyncProgressBar.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const route = useRoute();
const store = useSyncStore();
const toast = useToast();
const lastRun = ref(null);
const runForm = reactive({ mode: 'full', background: true });

const config = computed(() => store.currentConfig);

const modeOptions = [
  { value: 'full', label: 'Full' },
  { value: 'incremental', label: 'Incremental' },
];

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
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
