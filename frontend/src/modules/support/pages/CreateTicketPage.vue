<template>
  <div>
    <!-- <PageHeader
      title="Create support ticket"
      description="Log customer, technical, billing, or emergency support requests."
    /> -->
    <SupportSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
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
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
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
