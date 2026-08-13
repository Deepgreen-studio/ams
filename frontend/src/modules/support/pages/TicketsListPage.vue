<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
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
      <div
        v-for="card in cards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <TicketSearchFilter
          :model-value="store.filters"
          @submit="onFilter"
          @reset="onReset"
        />
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
            @click="onReset"
          >
            Reset filters
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
  ExclamationTriangleIcon,
  InboxIcon,
  PlayCircleIcon,
  PlusIcon,
  Squares2X2Icon,
  TicketIcon,
  UserMinusIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketSearchFilter from '@/modules/support/components/TicketSearchFilter.vue';
import TicketTable from '@/modules/support/components/TicketTable.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const store = useSupportTicketsStore();
const { can } = usePermissions();
const toast = useToast();
const route = useRoute();
const pendingArchive = ref(null);

const cards = computed(() => {
  const stats = store.statistics || {};
  const open = stats.open ?? 0;
  const inProgress = stats.in_progress ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const urgent = stats.urgent_or_critical ?? 0;

  return [
    {
      label: 'Total',
      value: stats.total ?? 0,
      hint: 'Matching current filters',
      icon: TicketIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Open',
      value: open,
      hint: open ? 'Awaiting first action' : 'No open tickets',
      icon: InboxIcon,
      iconBg: open ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: open ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'In progress',
      value: inProgress,
      hint: 'Currently being handled',
      icon: PlayCircleIcon,
      iconBg: inProgress ? 'bg-indigo-50' : 'bg-zinc-100',
      iconColor: inProgress ? 'text-indigo-500' : 'text-slate-500',
    },
    {
      label: 'Unassigned',
      value: unassigned,
      hint: unassigned ? 'Needs an agent' : 'All tickets assigned',
      icon: UserMinusIcon,
      iconBg: unassigned ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: unassigned ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Urgent / critical',
      value: urgent,
      hint: urgent ? 'Needs immediate attention' : 'No critical tickets',
      icon: ExclamationTriangleIcon,
      iconBg: urgent ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: urgent ? 'text-rose-500' : 'text-emerald-500',
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

  const queryFilters = {};
  ['status', 'priority', 'category', 'company', 'search'].forEach((key) => {
    if (route.query[key]) {
      queryFilters[key] = String(route.query[key]);
    }
  });
  store.fetchTickets(queryFilters).catch(() => {});
});

function onFilter(filters) {
  store.fetchTickets(filters).catch(() => {});
}

function onReset() {
  store.resetFilters();
  store.fetchTickets().catch(() => {});
}

function onPageChange(page) {
  store.fetchTickets({ page }).catch(() => {});
}

function onPerPage(perPage) {
  store.fetchTickets({ per_page: perPage, page: 1 }).catch(() => {});
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
    await store.fetchTickets();
  } catch {
    pendingArchive.value = null;
  }
}
</script>
