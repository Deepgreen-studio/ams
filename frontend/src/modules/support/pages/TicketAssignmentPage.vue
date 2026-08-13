<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.loading"
        @click="loadQueue()"
      >
        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': store.loading }" />
        Refresh
      </button>
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

    <div class="grid gap-4 lg:grid-cols-5">
      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 lg:col-span-3">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">Needs assignment</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Tickets without an agent, team, or department.
          </p>
        </div>

        <div v-if="store.loading && !store.tickets.length" class="space-y-3 px-6 py-5">
          <div v-for="n in 6" :key="n" class="h-16 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>

        <EmptyState
          v-else-if="!store.tickets.length"
          title="Assignment queue is clear"
          description="All active tickets have an assignment target."
        />

        <ul v-else class="space-y-1 px-3 py-3">
          <li
            v-for="ticket in store.tickets"
            :key="ticket.uuid"
            class="flex cursor-pointer items-center justify-between gap-3 rounded-[12px] px-4 py-3 transition"
            :class="ticketClass(ticket)"
            @click="selectTicket(ticket)"
          >
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <p class="truncate text-sm font-medium text-slate-900">{{ ticket.subject }}</p>
                <span
                  v-if="ticket.source === 'sms'"
                  class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-sky-100"
                >
                  SMS
                </span>
              </div>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ ticket.ticket_number }}
                <span v-if="ticket.company?.company_name"> · {{ ticket.company.company_name }}</span>
              </p>
            </div>
            <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
          </li>
        </ul>

        <div v-if="store.meta?.total" class="border-t border-zinc-100 px-6 py-4">
          <Pagination
            :meta="store.meta"
            :loading="store.loading"
            @change="onPageChange"
            @per-page="onPerPage"
          />
        </div>
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">
              {{ selected ? 'Assign ticket' : 'Select a ticket' }}
            </h2>
            <p v-if="selected" class="mt-0.5 text-xs text-slate-500">
              {{ selected.ticket_number }}
            </p>
          </div>
          <RouterLink
            v-if="selected && can('support.view')"
            :to="{ name: 'support.tickets.show', params: { id: selected.uuid } }"
            class="inline-flex h-10 items-center rounded-[12px] border border-zinc-200 px-3.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          >
            View
          </RouterLink>
        </div>

        <EmptyState
          v-if="!selected"
          title="No ticket selected"
          description="Choose a ticket from the queue to assign."
        />

        <template v-else>
          <div class="mb-5 rounded-[12px] bg-brand-50 p-4 ring-1 ring-brand-100">
            <div class="flex flex-wrap items-center gap-2">
              <p class="text-sm font-semibold text-slate-900">{{ selected.subject }}</p>
              <span
                v-if="selected.source === 'sms'"
                class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-sky-100"
              >
                SMS
              </span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
              {{ selected.company?.company_name || 'No company' }}
            </p>
            <div class="mt-3">
              <PriorityIndicator :priority="selected.priority" :label="selected.priority_label" />
            </div>
          </div>

          <AssignmentPanel
            :key="selected.uuid"
            :company-id="selected.company?.uuid || ''"
            :agents="store.agents"
            :loading="store.saving"
            submit-label="Assign"
            @submit="onAssign"
          />
        </template>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { ArrowPathIcon, PlusIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import AssignmentPanel from '@/modules/support/components/AssignmentPanel.vue';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const store = useSupportTicketsStore();
const { can } = usePermissions();
const toast = useToast();
const selected = ref(null);
const perPage = ref(10);

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

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  await store.fetchAgents();
  await loadQueue();
});

function ticketClass(ticket) {
  if (selected.value?.uuid === ticket.uuid) {
    return 'bg-brand-50 ring-1 ring-brand-500';
  }
  return 'hover:bg-zinc-50';
}

async function loadQueue(page = store.meta?.current_page || 1) {
  await store.fetchQueue({
    queue: 'assignment',
    page,
    per_page: perPage.value,
  }).catch(() => {});

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

function onPerPage(value) {
  perPage.value = value;
  loadQueue(1);
}

async function onAssign(payload) {
  if (!selected.value) {
    return;
  }

  try {
    await store.assignTicket(selected.value.uuid, payload);
    selected.value = null;
    await loadQueue();
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
