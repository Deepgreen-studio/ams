<template>
  <div>
    <PageHeader title="Email settings" description="SMTP configuration for outbound mail." />
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
  { key: 'smtp_host', label: 'SMTP host' },
  { key: 'smtp_port', label: 'SMTP port', type: 'number' },
  { key: 'smtp_username', label: 'SMTP username' },
  {
    key: 'smtp_password',
    label: 'SMTP password',
    type: 'password',
    placeholder: 'Leave unchanged to keep current',
  },
  { key: 'encryption', label: 'Encryption' },
  { key: 'from_name', label: 'Sender name' },
  { key: 'from_email', label: 'Sender email', type: 'email' },
];

onMounted(() => settingsStore.loadEmail());
async function onSubmit(payload) {
  await settingsStore.saveEmail(payload);
}
</script>
