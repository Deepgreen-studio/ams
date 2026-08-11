<template>
  <div>
    <SettingsTabs>
      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-6">
          <h2 class="text-base font-semibold text-slate-900">API settings</h2>
          <p class="mt-1 text-sm text-slate-500">
            Token and pagination defaults for the platform API.
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
