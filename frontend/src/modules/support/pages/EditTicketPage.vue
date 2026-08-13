<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="ticket"
        :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        View ticket
      </RouterLink>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !ticket" class="h-64 animate-pulse rounded-[12px] bg-zinc-100" />

    <div
      v-else-if="!ticket"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load this ticket</p>
      <p class="mt-1 text-xs text-slate-500">It may have been removed, or the request failed.</p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="loadTicket"
        >
          Retry
        </button>
        <RouterLink
          :to="{ name: 'support.tickets.index' }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back to tickets
        </RouterLink>
      </div>
    </div>

    <div v-else class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
      <div class="mb-6">
        <h2 class="text-base font-semibold text-slate-900">Edit ticket</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          {{ ticket.ticket_number }} · {{ ticket.subject }}
        </p>
      </div>
      <TicketForm
        :initial="ticket"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="router.push({ name: 'support.tickets.show', params: { id: ticket.uuid } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketForm from '@/modules/support/components/TicketForm.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const route = useRoute();
const router = useRouter();
const store = useSupportTicketsStore();
const toast = useToast();

const ticket = computed(() => store.currentTicket);

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
    if (!message || !ticket.value) return;
    toast.error(message);
    store.error = null;
  },
);

async function loadTicket() {
  store.currentTicket = null;
  store.error = null;
  try {
    await store.fetchTicket(route.params.id);
  } catch {
    /* empty state */
  }
}

onMounted(() => {
  loadTicket();
});

async function onSubmit(payload) {
  await store.updateTicket(route.params.id, payload);
  await router.push({ name: 'support.tickets.show', params: { id: route.params.id } });
}
</script>
