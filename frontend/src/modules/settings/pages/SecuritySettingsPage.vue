<template>
  <div>
    <SettingsTabs>
      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-6">
          <h2 class="text-base font-semibold text-slate-900">Security settings</h2>
          <p class="mt-1 text-sm text-slate-500">
            Password rules, sessions, and API rate limits.
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
  { key: 'password_min_length', label: 'Password min length', type: 'number' },
  { key: 'password_require_symbols', label: 'Require symbols', type: 'boolean' },
  { key: 'session_timeout_minutes', label: 'Session timeout (minutes)', type: 'number' },
  { key: 'login_max_attempts', label: 'Login max attempts', type: 'number' },
  { key: 'api_rate_limit', label: 'API rate limit / minute', type: 'number' },
];

onMounted(() => settingsStore.loadSecurity());
async function onSubmit(payload) {
  await settingsStore.saveSecurity(payload);
}
</script>
