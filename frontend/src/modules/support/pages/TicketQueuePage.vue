<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.loading"
        @click="loadQueue()"
      >
        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': store.loading }" />
        Refresh
      </button>
      <RouterLink
        v-if="can('support.create')"
        :to="{ name: 'support.tickets.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create ticket
      </RouterLink>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !store.statistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div v-for="n in 5" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <button
        v-for="card in cards"
        :key="card.queue"
        type="button"
        class="flex items-center justify-between gap-4 rounded-[12px] px-6 py-5 text-left transition"
        :class="cardClass(card)"
        @click="selectQueue(card.queue)"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="queue === card.queue ? 'bg-brand-100' : card.iconBg"
        >
          <component
            :is="card.icon"
            class="h-5 w-5"
            :class="queue === card.queue ? 'text-brand-600' : card.iconColor"
          />
        </div>
      </button>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 pt-5 sm:px-8">
        <div class="mb-1">
          <h2 class="text-base font-semibold text-slate-900">{{ activeQueueLabel }}</h2>
          <p class="mt-0.5 text-xs text-slate-500">Operational queues for work that still needs action.</p>
        </div>
        <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Queue filters">
          <button
            v-for="option in queueOptions"
            :key="option.value"
            type="button"
            class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
            :class="
              queue === option.value
                ? 'border-brand-600 text-brand-700'
                : 'border-transparent text-slate-500 hover:border-zinc-300 hover:text-slate-800'
            "
            @click="selectQueue(option.value)"
          >
            {{ option.label }}
          </button>
        </nav>
      </div>

      <TicketTable
        :tickets="store.tickets"
        :loading="store.loading"
        :framed="false"
        @archive="openArchive"
      >
        <template #empty-action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="selectQueue('open')"
          >
            View active queue
          </button>
          <RouterLink
            v-if="can('support.create')"
            :to="{ name: 'support.tickets.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create ticket
          </RouterLink>
        </template>
      </TicketTable>

      <div v-if="store.meta?.total" class="border-t border-zinc-100 px-6 py-4 sm:px-8">
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive ticket"
      :message="`Archive ${pendingArchive?.ticket_number || 'this ticket'}? It can be restored later.`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="pendingArchive = null"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ArrowPathIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  InboxIcon,
  PlusIcon,
  UserMinusIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketTable from '@/modules/support/components/TicketTable.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';
import { queueOptions } from '@/modules/support/utils/ticketOptions';

const store = useSupportTicketsStore();
const { can } = usePermissions();
const toast = useToast();
const route = useRoute();
const queue = ref(String(route.query.queue || 'open'));
const perPage = ref(10);
const pendingArchive = ref(null);

const activeQueueLabel = computed(
  () => queueOptions.find((option) => option.value === queue.value)?.label || 'Active queue'
);

function cardClass(card) {
  if (queue.value === card.queue) {
    return 'bg-brand-50 ring-1 ring-brand-500';
  }
  return 'bg-white ring-1 ring-zinc-100 hover:ring-brand-500';
}

const cards = computed(() => {
  const stats = store.statistics || {};
  const active =
    (stats.open ?? 0) +
    (stats.pending ?? 0) +
    (stats.in_progress ?? 0) +
    (stats.waiting_for_customer ?? 0) +
    (stats.reopened ?? 0);
  const unassigned = stats.unassigned ?? 0;
  const needsAssignment = stats.needs_assignment ?? 0;
  const waiting = stats.waiting_for_customer ?? 0;
  const critical = stats.urgent_or_critical ?? stats.critical_or_emergency ?? 0;

  return [
    {
      queue: 'open',
      label: 'Active',
      value: active,
      hint: 'Open work across queues',
      icon: InboxIcon,
      iconBg: active ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: active ? 'text-brand-500' : 'text-slate-500',
    },
    {
      queue: 'unassigned',
      label: 'Unassigned',
      value: unassigned,
      hint: unassigned ? 'Needs an agent' : 'All tickets assigned',
      icon: UserMinusIcon,
      iconBg: unassigned ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: unassigned ? 'text-amber-500' : 'text-slate-500',
    },
    {
      queue: 'assignment',
      label: 'Needs assignment',
      value: needsAssignment,
      hint: 'No agent, team, or department',
      icon: UserPlusIcon,
      iconBg: needsAssignment ? 'bg-violet-50' : 'bg-zinc-100',
      iconColor: needsAssignment ? 'text-violet-500' : 'text-slate-500',
    },
    {
      queue: 'waiting',
      label: 'Waiting',
      value: waiting,
      hint: 'Waiting for customer',
      icon: ClockIcon,
      iconBg: waiting ? 'bg-indigo-50' : 'bg-zinc-100',
      iconColor: waiting ? 'text-indigo-500' : 'text-slate-500',
    },
    {
      queue: 'critical',
      label: 'Critical',
      value: critical,
      hint: critical ? 'Needs immediate attention' : 'No critical tickets',
      icon: ExclamationTriangleIcon,
      iconBg: critical ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: critical ? 'text-rose-500' : 'text-emerald-500',
    },
  ];
});

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
  loadQueue();
});

function selectQueue(value) {
  queue.value = value;
  loadQueue(1);
}

async function loadQueue(page = store.meta?.current_page || 1) {
  await store.fetchQueue({
    queue: queue.value,
    page,
    per_page: perPage.value,
  }).catch(() => {});
}

function onPageChange(page) {
  loadQueue(page);
}

function onPerPage(value) {
  perPage.value = value;
  loadQueue(1);
}

function openArchive(ticket) {
  pendingArchive.value = ticket;
}

async function confirmArchive() {
  if (!pendingArchive.value) {
    return;
  }

  try {
    await store.archiveTicket(pendingArchive.value.uuid);
    pendingArchive.value = null;
    await loadQueue();
  } catch {
    pendingArchive.value = null;
  }
}
</script>
