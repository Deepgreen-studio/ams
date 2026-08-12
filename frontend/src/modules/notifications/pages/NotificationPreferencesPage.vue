<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="store.saving || store.loading"
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

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Channel preferences</h2>
            <p class="mt-1 text-sm text-slate-500">
              Choose which channels deliver each notification event. Future channels are locked until enabled.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
              Available
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-500">
              <span class="h-1.5 w-1.5 rounded-full bg-zinc-400" />
              Future
            </span>
          </div>
        </div>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 7" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!localPrefs.length"
        title="No preferences available"
        description="Notification preference events have not been configured yet."
        class="px-6 py-10 sm:px-8"
      />

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th
                class="sticky left-0 z-10 bg-white px-6 py-3 text-left text-sm font-semibold text-zinc-500 sm:px-8"
              >
                Event
              </th>
              <th
                v-for="channel in channels"
                :key="channel.key"
                class="px-4 py-3 text-center text-sm font-semibold text-zinc-500"
              >
                <div class="inline-flex flex-col items-center gap-1">
                  <span>{{ channel.label }}</span>
                  <span
                    v-if="!channel.enabled"
                    class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-400"
                  >
                    Future
                  </span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in localPrefs"
              :key="item.event_key"
              class="group border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="sticky left-0 z-10 bg-white px-6 py-4 sm:px-8 group-hover:bg-zinc-50">
                <p class="font-medium text-slate-900">{{ item.event_label }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.description }}</p>
              </td>
              <td
                v-for="channel in channels"
                :key="`${item.event_key}-${channel.key}`"
                class="px-4 py-4 text-center"
              >
                <label
                  class="inline-flex items-center justify-center"
                  :class="channel.enabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-40'"
                  :title="channel.enabled ? channel.label : `${channel.label} (Future)`"
                >
                  <input
                    v-model="item[channel.field]"
                    type="checkbox"
                    class="h-4 w-4 rounded border-zinc-300 accent-brand-600 text-brand-600 focus:ring-brand-500 focus:ring-offset-0 disabled:cursor-not-allowed"
                    :disabled="!channel.enabled"
                  />
                  <span class="sr-only">{{ channel.label }}</span>
                </label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="localPrefs.length"
        class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <p class="text-xs text-slate-500">
          Changes apply after you save preferences.
        </p>
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="store.saving"
          @click="save"
        >
          {{ store.saving ? 'Saving…' : 'Save preferences' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const localPrefs = ref([]);
const message = ref('');

const channels = [
  { key: 'email', label: 'Email', field: 'email_enabled', enabled: true },
  { key: 'in_app', label: 'In-App', field: 'in_app_enabled', enabled: true },
  { key: 'push', label: 'Push', field: 'push_enabled', enabled: false },
  { key: 'sms', label: 'SMS', field: 'sms_enabled', enabled: false },
  { key: 'whatsapp', label: 'WhatsApp', field: 'whatsapp_enabled', enabled: false },
  { key: 'slack', label: 'Slack', field: 'slack_enabled', enabled: false },
  { key: 'teams', label: 'Teams', field: 'teams_enabled', enabled: false },
  { key: 'webhook', label: 'Webhook', field: 'webhook_enabled', enabled: false },
];

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
