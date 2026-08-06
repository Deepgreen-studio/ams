<template>
  <div>
    <PageHeader
      title="API settings"
      description="Token and pagination defaults for the platform API."
    />
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
  { key: 'enabled', label: 'API enabled', type: 'boolean' },
  { key: 'default_page_size', label: 'Default page size', type: 'number' },
  { key: 'max_page_size', label: 'Max page size', type: 'number' },
  { key: 'token_expiration_minutes', label: 'Token expiration (minutes)', type: 'number' },
];

onMounted(() => settingsStore.loadApi());
async function onSubmit(payload) {
  await settingsStore.saveApi(payload);
}
</script>
