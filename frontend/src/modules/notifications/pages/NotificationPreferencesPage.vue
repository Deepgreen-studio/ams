<template>
  <div>
    <PageHeader title="Notification Preferences" description="Choose which channels deliver each event notification.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="save"
        >
          {{ store.saving ? 'Saving…' : 'Save preferences' }}
        </button>
      </template>
    </PageHeader>

    <NotificationsSubnav />

    <p v-if="message" class="mb-4 text-sm text-emerald-700">{{ message }}</p>
    <p v-if="store.error" class="mb-4 text-sm text-rose-600">{{ store.error }}</p>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Event</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">In-App</th>
            <th class="px-4 py-3">Push</th>
            <th class="px-4 py-3">SMS</th>
            <th class="px-4 py-3">WhatsApp</th>
            <th class="px-4 py-3">Slack</th>
            <th class="px-4 py-3">Teams</th>
            <th class="px-4 py-3">Webhook</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in localPrefs" :key="item.event_key">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.event_label }}</p>
              <p class="text-xs text-slate-500">{{ item.description }}</p>
            </td>
            <td class="px-4 py-3"><input v-model="item.email_enabled" type="checkbox" class="rounded border-slate-300" /></td>
            <td class="px-4 py-3"><input v-model="item.in_app_enabled" type="checkbox" class="rounded border-slate-300" /></td>
            <td class="px-4 py-3"><input v-model="item.push_enabled" type="checkbox" class="rounded border-slate-300" disabled title="Future" /></td>
            <td class="px-4 py-3"><input v-model="item.sms_enabled" type="checkbox" class="rounded border-slate-300" disabled title="Future" /></td>
            <td class="px-4 py-3"><input v-model="item.whatsapp_enabled" type="checkbox" class="rounded border-slate-300" disabled title="Future" /></td>
            <td class="px-4 py-3"><input v-model="item.slack_enabled" type="checkbox" class="rounded border-slate-300" disabled title="Future" /></td>
            <td class="px-4 py-3"><input v-model="item.teams_enabled" type="checkbox" class="rounded border-slate-300" disabled title="Future" /></td>
            <td class="px-4 py-3"><input v-model="item.webhook_enabled" type="checkbox" class="rounded border-slate-300" disabled title="Future" /></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const localPrefs = ref([]);
const message = ref('');

onMounted(async () => {
  await store.fetchPreferences();
  localPrefs.value = store.preferences.map((item) => ({ ...item }));
});

async function save() {
  message.value = '';
  await store.savePreferences(
    localPrefs.value.map((item) => ({
      event_key: item.event_key,
      email_enabled: !!item.email_enabled,
      in_app_enabled: !!item.in_app_enabled,
      sms_enabled: !!item.sms_enabled,
      push_enabled: !!item.push_enabled,
      whatsapp_enabled: !!item.whatsapp_enabled,
      slack_enabled: !!item.slack_enabled,
      teams_enabled: !!item.teams_enabled,
      webhook_enabled: !!item.webhook_enabled,
    }))
  );
  message.value = 'Preferences saved.';
}
</script>
