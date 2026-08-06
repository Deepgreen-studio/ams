<template>
  <div>
    <PageHeader title="Escalation Queue" description="Level 1–3, manager, and administrator escalations" />
    <SupportSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <select v-model="level" class="input w-auto" @change="reload">
        <option value="">All levels</option>
        <option value="level_1">Level 1</option>
        <option value="level_2">Level 2</option>
        <option value="level_3">Level 3</option>
        <option value="manager">Manager</option>
        <option value="administrator">Administrator</option>
      </select>
      <select v-model="status" class="input w-auto" @change="reload">
        <option value="">Open statuses</option>
        <option value="pending">Pending</option>
        <option value="notified">Notified</option>
        <option value="acknowledged">Acknowledged</option>
        <option value="resolved">Resolved</option>
      </select>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Ticket</th>
            <th class="px-4 py-3">Level</th>
            <th class="px-4 py-3">Trigger</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Triggered</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.escalations.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No escalations in queue.</td>
          </tr>
          <tr v-for="item in store.escalations" :key="item.uuid">
            <td class="px-4 py-3">
              <RouterLink
                v-if="item.ticket"
                :to="{ name: 'support.tickets.show', params: { id: item.ticket.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.ticket.ticket_number }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ item.ticket?.subject }}</p>
            </td>
            <td class="px-4 py-3">{{ item.level_label || item.level }}</td>
            <td class="px-4 py-3">{{ item.trigger_label || item.trigger }}</td>
            <td class="px-4 py-3">{{ item.status_label || item.status }}</td>
            <td class="px-4 py-3">{{ formatDate(item.triggered_at) }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-2">
                <button
                  v-if="item.status !== 'acknowledged' && item.status !== 'resolved'"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  :disabled="store.saving"
                  @click="store.acknowledgeEscalation(item.uuid)"
                >
                  Acknowledge
                </button>
                <button
                  v-if="item.status !== 'resolved'"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  :disabled="store.saving"
                  @click="store.resolveEscalation(item.uuid)"
                >
                  Resolve
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();
const level = ref('');
const status = ref('');

onMounted(reload);

function reload() {
  store.fetchEscalations({
    level: level.value || undefined,
    status: status.value || undefined,
  });
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>

<style scoped>
.input {
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}
</style>
