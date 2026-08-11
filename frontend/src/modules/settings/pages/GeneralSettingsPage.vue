<template>
  <div>
    <SettingsTabs>
      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-6">
          <h2 class="text-base font-semibold text-slate-900">General settings</h2>
          <p class="mt-1 text-sm text-slate-500">
            Application identity and global defaults.
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

      <div
        v-if="settingsStore.systemInfo"
        class="mt-6 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8"
      >
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          System information
        </h3>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
          <div
            v-for="(value, key) in settingsStore.systemInfo"
            :key="key"
            class="rounded-[12px] bg-zinc-50 px-4 py-3"
          >
            <dt class="text-xs font-medium text-slate-500">{{ key }}</dt>
            <dd class="mt-1 break-all text-sm font-medium text-slate-900">{{ value }}</dd>
          </div>
        </dl>
      </div>
    </SettingsTabs>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
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
