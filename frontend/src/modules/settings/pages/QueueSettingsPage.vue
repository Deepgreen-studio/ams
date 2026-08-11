<template>
  <div>
    <SettingsTabs>
      <div
        v-if="settingsStore.queueStatus"
        class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
      >
        <div
          v-for="card in statusCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ card.label }}
            </p>
            <p class="mt-1 truncate text-lg font-semibold tracking-tight text-slate-900">
              {{ card.value }}
            </p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-brand-50"
          >
            <component :is="card.icon" class="h-5 w-5 text-brand-500" />
          </div>
        </div>
      </div>

      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-6">
          <h2 class="text-base font-semibold text-slate-900">Queue settings</h2>
          <p class="mt-1 text-sm text-slate-500">
            Background job defaults and runtime status.
          </p>
        </div>
        <SettingsForm
          :fields="fields"
          :initial="settingsStore.current"
          :errors="settingsStore.fieldErrors"
          :error="settingsStore.error || ''"
          :success="settingsStore.successMessage || ''"
          :loading="settingsStore.saving"
          @submit="onSubmit"
        />
      </div>
    </SettingsTabs>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import {
  CircleStackIcon,
  QueueListIcon,
  ClockIcon,
  ServerStackIcon,
} from '@heroicons/vue/24/outline';
import SettingsForm from '@/modules/settings/components/SettingsForm.vue';
import SettingsTabs from '@/modules/settings/components/SettingsTabs.vue';
import { useSettingsStore } from '@/modules/settings/stores/settings';

const settingsStore = useSettingsStore();
const fields = [
  { key: 'default_connection', label: 'Default connection' },
  { key: 'default_queue', label: 'Default queue' },
  { key: 'retry_attempts', label: 'Retry attempts', type: 'number' },
  { key: 'job_timeout', label: 'Job timeout (seconds)', type: 'number' },
];

const statusCards = computed(() => {
  const status = settingsStore.queueStatus || {};
  return [
    {
      label: 'Connection',
      value: status.configured_connection || status.default || '—',
      icon: ServerStackIcon,
    },
    {
      label: 'Queue',
      value: status.default_queue || '—',
      icon: QueueListIcon,
    },
    {
      label: 'Size',
      value: status.size ?? '—',
      icon: CircleStackIcon,
    },
    {
      label: 'Timeout',
      value: status.job_timeout ?? '—',
      icon: ClockIcon,
    },
  ];
});

onMounted(() => settingsStore.loadQueue());
async function onSubmit(payload) {
  await settingsStore.saveQueue(payload);
}
</script>
