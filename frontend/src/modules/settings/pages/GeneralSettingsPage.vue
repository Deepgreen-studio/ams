<template>
  <div>
    <PageHeader title="General settings" description="Application identity and global defaults." />
    <SettingsTabs>
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
      <div
        v-if="settingsStore.systemInfo"
        class="mt-6 rounded-xl border border-slate-200 bg-white p-6"
      >
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
          System information
        </h3>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
          <div v-for="(value, key) in settingsStore.systemInfo" :key="key">
            <dt class="text-xs text-slate-500">{{ key }}</dt>
            <dd class="text-sm text-slate-900">{{ value }}</dd>
          </div>
        </dl>
      </div>
    </SettingsTabs>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SettingsForm from '@/modules/settings/components/SettingsForm.vue';
import SettingsTabs from '@/modules/settings/components/SettingsTabs.vue';
import { useSettingsStore } from '@/modules/settings/stores/settings';

const settingsStore = useSettingsStore();
const fields = [
  { key: 'app_name', label: 'Application name' },
  { key: 'app_url', label: 'Application URL', type: 'url' },
  { key: 'timezone', label: 'Timezone' },
  { key: 'language', label: 'Language' },
  { key: 'currency', label: 'Currency' },
  { key: 'date_format', label: 'Date format' },
  { key: 'time_format', label: 'Time format' },
  { key: 'maintenance_mode', label: 'Maintenance mode', type: 'boolean' },
];

onMounted(async () => {
  await settingsStore.loadGeneral();
  await settingsStore.fetchSystemInfo();
});

async function onSubmit(payload) {
  await settingsStore.saveGeneral(payload);
}
</script>
