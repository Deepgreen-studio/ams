<template>
  <div>
    <!-- <PageHeader
      title="Security settings"
      description="Password rules, sessions, and API rate limits."
    /> -->
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
// import PageHeader from '@/components/ui/PageHeader.vue';
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
