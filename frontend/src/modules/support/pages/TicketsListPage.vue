<template>
  <div>
    <!-- <PageHeader
      title="Support tickets"
      description="Search, filter, and manage customer support tickets."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'support.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          :to="{ name: 'support.tickets.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create ticket
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'support.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          v-if="can('support.create')"
          :to="{ name: 'support.tickets.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create ticket
        </RouterLink>
    </Teleport>

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

    <div v-if="store.statistics" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="space-y-4">
      <TicketSearchFilter
        :model-value="store.filters"
        @submit="onFilter"
        @reset="onReset"
      />

      <TicketTable
        :tickets="store.tickets"
        :loading="store.loading"
        @archive="openArchive"
      >
        <template #empty-action>
          <RouterLink
            v-if="can('support.create')"
            :to="{ name: 'support.tickets.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create ticket
          </RouterLink>
        </template>
      </TicketTable>

      <Pagination
        :meta="store.meta"
        :loading="store.loading"
        @change="onPageChange"
      />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingArchive)"
      title="Archive ticket"
      :message="`Archive ${pendingArchive?.ticket_number || 'this ticket'}? It can be restored later.`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="pendingArchive = null"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { usePermissions } from '@/composables/usePermissions';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketSearchFilter from '@/modules/support/components/TicketSearchFilter.vue';
import TicketTable from '@/modules/support/components/TicketTable.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const store = useSupportTicketsStore();
const { can } = usePermissions();
const route = useRoute();
const pendingArchive = ref(null);

const statCards = computed(() => [
  { label: 'Total', value: store.statistics?.total ?? 0 },
  { label: 'Open', value: store.statistics?.open ?? 0 },
  { label: 'In progress', value: store.statistics?.in_progress ?? 0 },
  { label: 'Unassigned', value: store.statistics?.unassigned ?? 0 },
  { label: 'Urgent / critical', value: store.statistics?.urgent_or_critical ?? 0 },
]);

onMounted(() => {
  const queryFilters = {};
  ['status', 'priority', 'category', 'company', 'search'].forEach((key) => {
    if (route.query[key]) {
      queryFilters[key] = String(route.query[key]);
    }
  });
  store.fetchTickets(queryFilters);
});

function onFilter(filters) {
  store.fetchTickets(filters);
}

function onReset() {
  store.resetFilters();
  store.fetchTickets();
}

function onPageChange(page) {
  store.fetchTickets({ page });
}

function openArchive(ticket) {
  pendingArchive.value = ticket;
}

async function confirmArchive() {
  if (!pendingArchive.value) {
    return;
  }

  await store.archiveTicket(pendingArchive.value.uuid);
  pendingArchive.value = null;
  await store.fetchTickets();
}
</script>
