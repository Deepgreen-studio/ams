<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.sla.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ClockIcon class="h-4 w-4" />
        SLA dashboard
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.loading"
        @click="loadQueue()"
      >
        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': store.loading }" />
        Refresh
      </button>
    </Teleport>

    <SupportSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="flex flex-col gap-4 border-b border-zinc-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <div>
          <h2 class="text-base font-semibold text-slate-900">Escalation queue</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Level 1–3, manager, and administrator escalations that still need action.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <SelectBox
            v-model="level"
            wrapper-class="min-w-[12rem]"
            :options="levelOptions"
            @change="onFilterChange"
          />
          <SelectBox
            v-model="status"
            wrapper-class="min-w-[12rem]"
            :options="statusOptions"
            @change="onFilterChange"
          />
        </div>
      </div>

      <div v-if="store.loading && !store.escalations.length" class="space-y-3 px-6 py-5 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.escalations.length"
        title="No escalations in queue"
        description="Open SLA breaches will appear here when they are escalated."
      >
        <template #action>
          <RouterLink
            :to="{ name: 'support.sla.dashboard' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            View SLA timers
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Ticket</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Level</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Trigger
              </th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
                Triggered
              </th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.escalations"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <RouterLink
                  v-if="item.ticket"
                  :to="{ name: 'support.tickets.show', params: { id: item.ticket.uuid } }"
                  class="font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.ticket.ticket_number }}
                </RouterLink>
                <p v-else class="font-medium text-slate-900">Unknown ticket</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.ticket?.subject || '—' }}</p>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                  :class="levelTone(item.level)"
                >
                  {{ item.level_label || item.level }}
                </span>
              </td>
              <td class="hidden px-5 py-4 md:table-cell">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                  :class="triggerTone(item.trigger)"
                >
                  {{ item.trigger_label || item.trigger }}
                </span>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                  :class="statusTone(item.status)"
                >
                  {{ item.status_label || item.status }}
                </span>
              </td>
              <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
                {{ formatDate(item.triggered_at) }}
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-2">
                  <button
                    v-if="item.status !== 'acknowledged' && item.status !== 'resolved'"
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-zinc-100 disabled:opacity-60"
                    :disabled="store.saving"
                    @click="acknowledge(item)"
                  >
                    Acknowledge
                  </button>
                  <button
                    v-if="item.status !== 'resolved'"
                    type="button"
                    class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-60"
                    :disabled="store.saving"
                    @click="resolve(item)"
                  >
                    Resolve
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.escalationMeta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.escalationMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { ArrowPathIcon, ClockIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();
const toast = useToast();
const level = ref('');
const status = ref('');
const perPage = ref(10);

const levelOptions = [
  { value: '', label: 'All levels' },
  { value: 'level_1', label: 'Level 1' },
  { value: 'level_2', label: 'Level 2' },
  { value: 'level_3', label: 'Level 3' },
  { value: 'manager', label: 'Manager' },
  { value: 'administrator', label: 'Administrator' },
];

const statusOptions = [
  { value: '', label: 'Open statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'notified', label: 'Notified' },
  { value: 'acknowledged', label: 'Acknowledged' },
  { value: 'resolved', label: 'Resolved' },
];

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  loadQueue().catch(() => {});
});

function queueParams(page = store.escalationMeta?.current_page || 1) {
  return {
    page,
    per_page: perPage.value,
    level: level.value || undefined,
    status: status.value || undefined,
  };
}

async function loadQueue(page = 1) {
  await store.fetchEscalations(queueParams(page));
}

function onFilterChange() {
  loadQueue(1).catch(() => {});
}

function onPageChange(page) {
  loadQueue(page).catch(() => {});
}

function onPerPage(value) {
  perPage.value = value;
  loadQueue(1).catch(() => {});
}

async function acknowledge(item) {
  try {
    await store.acknowledgeEscalation(item.uuid);
  } catch {
    // Toast is shown from store.error.
  }
}

async function resolve(item) {
  try {
    await store.resolveEscalation(item.uuid);
  } catch {
    // Toast is shown from store.error.
  }
}

function levelTone(levelValue) {
  switch (levelValue) {
    case 'level_1':
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
    case 'level_2':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'level_3':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'manager':
      return 'bg-brand-50 text-brand-700 ring-brand-500/20';
    case 'administrator':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    default:
      return 'bg-slate-50 text-slate-600 ring-slate-500/20';
  }
}

function triggerTone(trigger) {
  if (String(trigger || '').includes('breached')) {
    return 'bg-rose-50 text-rose-700 ring-rose-600/20';
  }
  if (String(trigger || '').includes('at_risk')) {
    return 'bg-amber-50 text-amber-800 ring-amber-600/20';
  }
  return 'bg-slate-50 text-slate-600 ring-slate-500/20';
}

function statusTone(statusValue) {
  switch (statusValue) {
    case 'pending':
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
    case 'notified':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'acknowledged':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'resolved':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    default:
      return 'bg-slate-50 text-slate-600 ring-slate-500/20';
  }
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
