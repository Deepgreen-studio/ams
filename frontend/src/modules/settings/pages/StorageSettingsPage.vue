<template>
  <div>
    <PageHeader title="Storage settings" description="Disks, upload limits, and cloud readiness." />
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
  { key: 'default_disk', label: 'Default disk' },
  { key: 'public_disk', label: 'Public disk' },
  { key: 'private_disk', label: 'Private disk' },
  { key: 'max_upload_kb', label: 'Max upload (KB)', type: 'number' },
  { key: 'cloud_provider', label: 'Cloud provider (s3/gcs/azure)' },
];

onMounted(() => settingsStore.loadStorage());
async function onSubmit(payload) {
  await settingsStore.saveStorage(payload);
}
</script>
