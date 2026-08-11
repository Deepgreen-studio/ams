<template>
  <div>
    <SettingsTabs>
      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-6">
          <h2 class="text-base font-semibold text-slate-900">Email settings</h2>
          <p class="mt-1 text-sm text-slate-500">
            SMTP configuration for outbound mail.
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
  { key: 'smtp_host', label: 'SMTP host' },
  { key: 'smtp_port', label: 'SMTP port', type: 'number' },
  { key: 'smtp_username', label: 'SMTP username' },
  {
    key: 'smtp_password',
    label: 'SMTP password',
    type: 'password',
    placeholder: 'Leave unchanged to keep current',
  },
  { key: 'encryption', label: 'Encryption', hint: 'tls, ssl, or none' },
  { key: 'from_name', label: 'Sender name' },
  { key: 'from_email', label: 'Sender email', type: 'email' },
];

onMounted(() => settingsStore.loadEmail());
async function onSubmit(payload) {
  await settingsStore.saveEmail(payload);
}
</script>
