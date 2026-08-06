<template>
  <div>
    <PageHeader
      title="Kanban board"
      description="Visual ticket workflow across all support statuses."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'support.tickets.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create ticket
        </RouterLink>
      </template>
    </PageHeader>

    <SupportSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <div class="w-full sm:w-64">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Company</label>
        <select v-model="company" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="">All companies</option>
          <option v-for="item in companies" :key="item.uuid" :value="item.uuid">
            {{ item.company_name }}
          </option>
        </select>
      </div>
      <button
        type="button"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="loadBoard"
      >
        Refresh
      </button>
    </div>

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-64 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <div v-else class="flex gap-4 overflow-x-auto pb-4">
      <div
        v-for="column in store.boardColumns"
        :key="column.status"
        class="w-72 shrink-0 rounded-xl border border-slate-200 bg-slate-50"
      >
        <div class="flex items-center justify-between border-b border-slate-200 px-3 py-3">
          <TicketStatusBadge :status="column.status" :label="column.label" />
          <span class="text-xs font-medium text-slate-500">{{ column.count }}</span>
        </div>
        <div class="space-y-3 p-3">
          <EmptyState
            v-if="!column.tickets?.length"
            title="Empty"
            description="No tickets in this column."
          />
          <RouterLink
            v-for="ticket in column.tickets"
            :key="ticket.uuid"
            :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
            class="block rounded-lg border border-slate-200 bg-white p-3 shadow-sm transition hover:border-brand-300"
          >
            <div class="mb-2 flex items-start justify-between gap-2">
              <p class="text-sm font-medium text-slate-900">{{ ticket.subject }}</p>
              <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            </div>
            <p class="text-xs text-slate-500">{{ ticket.ticket_number }}</p>
            <p class="mt-2 text-xs text-slate-500">
              {{ ticket.assignee?.full_name || 'Unassigned' }}
            </p>
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { companyService } from '@/modules/companies/services/companyService';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketStatusBadge from '@/modules/support/components/TicketStatusBadge.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const store = useSupportTicketsStore();
const companies = ref([]);
const company = ref('');

onMounted(async () => {
  try {
    const { data } = await companyService.list({ per_page: 100 });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
  await loadBoard();
});

async function loadBoard() {
  await store.fetchBoard({ company: company.value || undefined });
}
</script>
