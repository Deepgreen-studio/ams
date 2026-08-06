<template>
  <div>
    <PageHeader
      title="Support Center"
      description="Enterprise helpdesk overview across all connected applications."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'support.tickets.board' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Kanban
        </RouterLink>
        <RouterLink
          :to="{ name: 'support.tickets.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          All tickets
        </RouterLink>
        <RouterLink
          :to="{ name: 'support.tickets.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create ticket
        </RouterLink>
      </template>
    </PageHeader>

    <SupportSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent open tickets</h2>
          <RouterLink
            :to="{ name: 'support.tickets.index', query: { status: 'open' } }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View open
          </RouterLink>
        </div>
        <div v-if="store.loading && !recentOpen.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <EmptyState
          v-else-if="!recentOpen.length"
          title="No open tickets"
          description="Open tickets will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="ticket in recentOpen" :key="ticket.uuid" class="flex items-center justify-between py-3">
            <div>
              <RouterLink
                :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ ticket.subject }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ ticket.ticket_number }}</p>
            </div>
            <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Urgent & critical</h2>
          <RouterLink
            :to="{ name: 'support.tickets.index', query: { priority: 'urgent' } }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            View queue
          </RouterLink>
        </div>
        <div v-if="store.loading && !urgent.length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
        </div>
        <EmptyState
          v-else-if="!urgent.length"
          title="No urgent tickets"
          description="High-priority tickets will appear here."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="ticket in urgent" :key="ticket.uuid" class="flex items-center justify-between py-3">
            <div>
              <RouterLink
                :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ ticket.subject }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ ticket.ticket_number }}</p>
            </div>
            <TicketStatusBadge :status="ticket.status" :label="ticket.status_label" />
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketStatusBadge from '@/modules/support/components/TicketStatusBadge.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const store = useSupportTicketsStore();

const statistics = computed(() => store.dashboard?.statistics || store.statistics || {});
const recentOpen = computed(() => store.dashboard?.recent_open?.items ?? []);
const urgent = computed(() => store.dashboard?.urgent?.items ?? []);

const statCards = computed(() => [
  { label: 'Total', value: statistics.value.total ?? 0 },
  { label: 'Open', value: statistics.value.open ?? 0 },
  { label: 'In progress', value: statistics.value.in_progress ?? 0 },
  { label: 'Unassigned', value: statistics.value.unassigned ?? 0 },
  { label: 'Urgent / critical', value: statistics.value.urgent_or_critical ?? 0 },
  { label: 'Resolved', value: statistics.value.resolved ?? 0 },
  { label: 'Closed', value: statistics.value.closed ?? 0 },
  { label: 'Pending', value: statistics.value.pending ?? 0 },
]);

onMounted(() => {
  store.fetchDashboard();
});
</script>
