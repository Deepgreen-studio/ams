<template>
  <div>
    <PageHeader
      title="Assignment screen"
      description="Assign tickets manually, automatically, or by department, team, and agent."
    />

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

    <div class="grid gap-4 lg:grid-cols-5">
      <div class="lg:col-span-3">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Needs assignment</h2>
          <button
            type="button"
            class="text-xs font-medium text-brand-700 hover:underline"
            @click="loadQueue"
          >
            Refresh
          </button>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <div v-if="store.loading" class="space-y-3 p-6">
            <div v-for="n in 4" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
          </div>
          <EmptyState
            v-else-if="!store.tickets.length"
            title="Assignment queue is clear"
            description="All active tickets have an assignment target."
          />
          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="ticket in store.tickets"
              :key="ticket.uuid"
              class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50"
              :class="{ 'bg-brand-50/40': selected?.uuid === ticket.uuid }"
              @click="selectTicket(ticket)"
            >
              <div>
                <p class="text-sm font-medium text-slate-900">{{ ticket.subject }}</p>
                <p class="text-xs text-slate-500">
                  {{ ticket.ticket_number }} · {{ ticket.company?.company_name || '—' }}
                </p>
              </div>
              <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            </li>
          </ul>
        </div>
        <Pagination class="mt-4" :meta="store.meta" :loading="store.loading" @change="onPageChange" />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
        <h2 class="mb-4 text-sm font-semibold text-slate-900">
          {{ selected ? `Assign ${selected.ticket_number}` : 'Select a ticket' }}
        </h2>
        <EmptyState
          v-if="!selected"
          title="No ticket selected"
          description="Choose a ticket from the queue to assign."
        />
        <AssignmentPanel
          v-else
          :company-id="selected.company?.uuid || ''"
          :agents="store.agents"
          :loading="store.saving"
          :error="store.error || ''"
          @submit="onAssign"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import AssignmentPanel from '@/modules/support/components/AssignmentPanel.vue';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const store = useSupportTicketsStore();
const selected = ref(null);

onMounted(async () => {
  await store.fetchAgents();
  await loadQueue();
});

async function loadQueue(page = 1) {
  await store.fetchQueue({ queue: 'assignment', page, per_page: 10 });
  if (selected.value) {
    selected.value = store.tickets.find((ticket) => ticket.uuid === selected.value.uuid) || null;
  }
}

function selectTicket(ticket) {
  selected.value = ticket;
}

function onPageChange(page) {
  loadQueue(page);
}

async function onAssign(payload) {
  if (!selected.value) {
    return;
  }

  await store.assignTicket(selected.value.uuid, payload);
  selected.value = null;
  await loadQueue();
}
</script>
