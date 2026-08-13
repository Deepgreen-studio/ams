<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.tickets.board' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ViewColumnsIcon class="h-4 w-4" />
        Kanban
      </RouterLink>
      <RouterLink
        :to="{ name: 'support.tickets.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <TicketIcon class="h-4 w-4" />
        All tickets
      </RouterLink>
      <RouterLink
        :to="{ name: 'support.tickets.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create ticket
      </RouterLink>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !hasDashboard" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasDashboard"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load support dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading ticket metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else>
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in cards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">
              {{ card.value }}
            </p>
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

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Recent open tickets</h2>
              <p class="mt-0.5 text-xs text-slate-500">Newest tickets waiting for a response</p>
            </div>
            <RouterLink
              :to="{ name: 'support.tickets.index', query: { status: 'open' } }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View open
            </RouterLink>
          </div>
          <div v-if="store.loading && !recentOpen.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!recentOpen.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No open tickets</p>
            <p class="mt-1 text-xs text-slate-500">Open tickets will appear here as they are created.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="ticket in recentOpen"
              :key="ticket.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ ticket.subject }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">{{ ticket.ticket_number }}</p>
              </div>
              <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Urgent & critical</h2>
              <p class="mt-0.5 text-xs text-slate-500">Highest-priority tickets that need attention</p>
            </div>
            <RouterLink
              :to="{ name: 'support.tickets.queue' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View queue
            </RouterLink>
          </div>
          <div v-if="store.loading && !urgent.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!urgent.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No urgent tickets</p>
            <p class="mt-1 text-xs text-slate-500">Critical and emergency tickets will show here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="ticket in urgent"
              :key="ticket.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ ticket.subject }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">{{ ticket.ticket_number }}</p>
              </div>
              <TicketStatusBadge :status="ticket.status" :label="ticket.status_label" />
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArchiveBoxIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  InboxIcon,
  PlayCircleIcon,
  PlusIcon,
  ShieldCheckIcon,
  TicketIcon,
  UserMinusIcon,
  ViewColumnsIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketStatusBadge from '@/modules/support/components/TicketStatusBadge.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const store = useSupportTicketsStore();
const toast = useToast();

const statistics = computed(() => store.dashboard?.statistics || store.statistics || {});
const recentOpen = computed(() => store.dashboard?.recent_open?.items ?? []);
const urgent = computed(() => store.dashboard?.urgent?.items ?? []);
const hasDashboard = computed(() => Boolean(store.dashboard || store.statistics));

const cards = computed(() => {
  const stats = statistics.value;
  const open = stats.open ?? 0;
  const inProgress = stats.in_progress ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const urgentCount = stats.urgent_or_critical ?? 0;
  const pending = stats.pending ?? 0;

  return [
    {
      label: 'Total',
      value: stats.total ?? 0,
      hint: 'All support tickets',
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
      value: urgentCount,
      hint: urgentCount ? 'Needs immediate attention' : 'No critical tickets',
      icon: ExclamationTriangleIcon,
      iconBg: urgentCount ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: urgentCount ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'Resolved',
      value: stats.resolved ?? 0,
      hint: 'Waiting to close',
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-500',
    },
    {
      label: 'Closed',
      value: stats.closed ?? 0,
      hint: 'Completed tickets',
      icon: ArchiveBoxIcon,
      iconBg: 'bg-zinc-100',
      iconColor: 'text-slate-500',
    },
    {
      label: 'Pending',
      value: pending,
      hint: pending ? 'On hold or waiting' : 'Nothing pending',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
  ];
});

const healthMessage = computed(() => {
  const stats = statistics.value;
  const urgentCount = stats.urgent_or_critical ?? 0;
  const unassigned = stats.unassigned ?? 0;
  const open = stats.open ?? 0;

  if (urgentCount > 0) {
    return `${urgentCount} urgent or critical ticket${urgentCount === 1 ? '' : 's'} need immediate attention.`;
  }
  if (unassigned > 0) {
    return `${unassigned} unassigned ticket${unassigned === 1 ? '' : 's'} waiting for an agent.`;
  }
  if (open > 0) {
    return `${open} open ticket${open === 1 ? '' : 's'} in the queue.`;
  }
  return 'Support queue is healthy. No urgent or unassigned tickets.';
});

const healthTone = computed(() => {
  const stats = statistics.value;
  if ((stats.urgent_or_critical ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if ((stats.unassigned ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  if ((stats.open ?? 0) > 0) return 'bg-sky-50 text-sky-800';
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const stats = statistics.value;
  if ((stats.urgent_or_critical ?? 0) > 0) return ExclamationTriangleIcon;
  if ((stats.unassigned ?? 0) > 0 || (stats.open ?? 0) > 0) return ClockIcon;
  return ShieldCheckIcon;
});

async function reload() {
  try {
    await store.fetchDashboard();
  } catch {
    toast.error(store.error || 'Unable to load support dashboard');
  }
}

onMounted(() => {
  reload();
});
</script>
