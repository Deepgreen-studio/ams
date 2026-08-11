<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="store.saving"
        @click="save"
      >
        {{ store.saving ? 'Saving…' : 'Save preferences' }}
      </button>
    </Teleport>

    <NotificationsSubnav />

    <div
      v-if="message"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ message }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div v-else class="overflow-x-auto rounded-[12px] bg-white ring-1 ring-zinc-100">
      <table class="min-w-full divide-y divide-zinc-100 text-sm">
        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-5 py-3.5">Event</th>
            <th class="px-5 py-3.5">Email</th>
            <th class="px-5 py-3.5">In-App</th>
            <th class="px-5 py-3.5">Push</th>
            <th class="px-5 py-3.5">SMS</th>
            <th class="px-5 py-3.5">WhatsApp</th>
            <th class="px-5 py-3.5">Slack</th>
            <th class="px-5 py-3.5">Teams</th>
            <th class="px-5 py-3.5">Webhook</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
          <tr v-for="item in localPrefs" :key="item.event_key" class="hover:bg-zinc-50/80">
            <td class="px-5 py-4">
              <p class="font-medium text-slate-900">{{ item.event_label }}</p>
              <p class="text-xs text-slate-500">{{ item.description }}</p>
            </td>
            <td class="px-5 py-4">
              <input v-model="item.email_enabled" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
            </td>
            <td class="px-5 py-4">
              <input v-model="item.in_app_enabled" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
            </td>
            <td class="px-5 py-4">
              <input v-model="item.push_enabled" type="checkbox" class="rounded border-zinc-300" disabled title="Future" />
            </td>
            <td class="px-5 py-4">
              <input v-model="item.sms_enabled" type="checkbox" class="rounded border-zinc-300" disabled title="Future" />
            </td>
            <td class="px-5 py-4">
              <input v-model="item.whatsapp_enabled" type="checkbox" class="rounded border-zinc-300" disabled title="Future" />
            </td>
            <td class="px-5 py-4">
              <input v-model="item.slack_enabled" type="checkbox" class="rounded border-zinc-300" disabled title="Future" />
            </td>
            <td class="px-5 py-4">
              <input v-model="item.teams_enabled" type="checkbox" class="rounded border-zinc-300" disabled title="Future" />
            </td>
            <td class="px-5 py-4">
              <input v-model="item.webhook_enabled" type="checkbox" class="rounded border-zinc-300" disabled title="Future" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
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
