<template>
  <div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">My support tickets</h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ store.profile?.customer?.display_name || 'Customer' }}
          <span v-if="store.profile?.customer?.company"> · {{ store.profile.customer.company.company_name }}</span>
        </p>
      </div>
      <RouterLink
        :to="{ name: 'portal.tickets.create' }"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Submit a ticket
      </RouterLink>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div
      v-else-if="store.tickets.length === 0"
      class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500"
    >
      No tickets yet. Submit your first request.
    </div>

    <div v-else class="space-y-3">
      <RouterLink
        v-for="ticket in store.tickets"
        :key="ticket.uuid"
        :to="{ name: 'portal.tickets.show', params: { id: ticket.uuid } }"
        class="block rounded-xl border border-slate-200 bg-white px-4 py-4 hover:border-brand-300 hover:bg-brand-50/30"
      >
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div>
            <p class="font-medium text-slate-900">{{ ticket.subject }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ ticket.ticket_number }} · {{ ticket.priority_label || ticket.priority }} ·
              {{ ticket.status_label || ticket.status }}
            </p>
          </div>
          <span class="text-xs text-slate-400">{{ formatDate(ticket.created_at) }}</span>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { usePortalSupportStore } from '@/modules/portal/stores/portalSupport';

const store = usePortalSupportStore();

onMounted(async () => {
  await Promise.all([store.fetchProfile().catch(() => {}), store.fetchTickets({ per_page: 50 })]);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
