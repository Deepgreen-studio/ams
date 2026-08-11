<template>
  <div>
    <SettingsTabs>
      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-6">
          <h2 class="text-base font-semibold text-slate-900">Storage settings</h2>
          <p class="mt-1 text-sm text-slate-500">
            Disks, upload limits, and cloud readiness.
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
import { onMounted } from 'vue';
import SettingsForm from '@/modules/settings/components/SettingsForm.vue';
import SettingsTabs from '@/modules/settings/components/SettingsTabs.vue';
import { useSettingsStore } from '@/modules/settings/stores/settings';

const settingsStore = useSettingsStore();
const fields = [
  { key: 'default_disk', label: 'Default disk' },
  { key: 'public_disk', label: 'Public disk' },
  { key: 'private_disk', label: 'Private disk' },
  { key: 'max_upload_kb', label: 'Max upload (KB)', type: 'number' },
  {
    key: 'cloud_provider',
    label: 'Cloud provider',
    hint: 's3, gcs, azure, or leave empty for local',
  },
];

onMounted(() => settingsStore.loadStorage());
async function onSubmit(payload) {
  await settingsStore.saveStorage(payload);
}
</script>
