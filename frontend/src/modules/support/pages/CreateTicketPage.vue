<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.tickets.index' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        All tickets
      </RouterLink>
    </Teleport>

    <SupportSubnav />

    <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
      <TicketForm
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Create ticket"
        @submit="onSubmit"
        @cancel="router.push({ name: 'support.tickets.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketForm from '@/modules/support/components/TicketForm.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const router = useRouter();
const store = useSupportTicketsStore();

async function onSubmit(payload) {
  const ticket = await store.createTicket(payload);
  await router.push({ name: 'support.tickets.show', params: { id: ticket.uuid } });
}
</script>
