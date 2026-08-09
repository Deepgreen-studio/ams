<template>
  <div>
    <!-- <PageHeader
      title="Ticket queue"
      description="Operational queues for open, unassigned, critical, and reopened tickets."
    /> -->

    <SupportSubnav />

    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="option in queueOptions"
        :key="option.value"
        type="button"
        class="rounded-lg px-3 py-2 text-sm font-medium"
        :class="
          queue === option.value
            ? 'bg-brand-600 text-white'
            : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'
        "
        @click="selectQueue(option.value)"
      >
        {{ option.label }}
      </button>
    </div>

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <TicketTable :tickets="store.tickets" :loading="store.loading" @archive="archiveTicket">
      <template #empty-action>
        <RouterLink
          :to="{ name: 'support.tickets.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create ticket
        </RouterLink>
      </template>
    </TicketTable>

    <Pagination class="mt-4" :meta="store.meta" :loading="store.loading" @change="onPageChange" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketTable from '@/modules/support/components/TicketTable.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';
import { queueOptions } from '@/modules/support/utils/ticketOptions';

const store = useSupportTicketsStore();
const route = useRoute();
const queue = ref(String(route.query.queue || 'open'));

onMounted(() => {
  loadQueue();
});

function selectQueue(value) {
  queue.value = value;
  loadQueue();
}

async function loadQueue(page = 1) {
  await store.fetchQueue({ queue: queue.value, page, per_page: 10 });
}

function onPageChange(page) {
  loadQueue(page);
}

async function archiveTicket(ticket) {
  await store.archiveTicket(ticket.uuid);
  await loadQueue();
}
</script>
