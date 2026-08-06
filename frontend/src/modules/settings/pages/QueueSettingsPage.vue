<template>
  <div>
    <PageHeader title="Queue settings" description="Background job defaults and runtime status." />
    <SettingsTabs>
      <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" v-if="settingsStore.queueStatus">
        <div
          v-for="(value, key) in statusCards"
          :key="key"
          class="rounded-xl border border-slate-200 bg-white px-4 py-3"
        >
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ key }}</p>
          <p class="mt-1 text-sm font-semibold text-slate-900">{{ value }}</p>
        </div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6">
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
import PageHeader from '@/components/ui/PageHeader.vue';
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
  return {
    connection: status.configured_connection || status.default || '—',
    queue: status.default_queue || '—',
    size: status.size ?? '—',
    timeout: status.job_timeout ?? '—',
  };
});

onMounted(() => settingsStore.loadQueue());
async function onSubmit(payload) {
  await settingsStore.saveQueue(payload);
}
</script>
