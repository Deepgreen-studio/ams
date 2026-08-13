<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.loading || store.saving"
        @click="loadBoard"
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

    <div class="mb-4 rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <div>
          <h2 class="text-base font-semibold text-slate-900">Kanban board</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Drag tickets between columns to update status, or drag the board to scroll.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <SelectBox
            v-model="company"
            wrapper-class="min-w-[14rem]"
            :options="companyOptions"
            @change="loadBoard"
          />
        </div>
      </div>
    </div>

    <div v-if="store.loading && !store.boardColumns.length" class="flex gap-4 overflow-x-auto pb-2">
      <div v-for="n in 5" :key="n" class="h-[28rem] w-72 shrink-0 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="!store.boardColumns.length"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load the kanban board</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading ticket columns again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="loadBoard"
      >
        Retry
      </button>
    </div>

    <div
      v-else
      ref="boardRef"
      class="flex cursor-grab gap-4 overflow-x-auto pb-2"
      :class="isPanning ? 'cursor-grabbing select-none' : ''"
      @pointerdown="onBoardPointerDown"
      @pointermove="onBoardPointerMove"
      @pointerup="endBoardPan"
      @pointercancel="endBoardPan"
      @lostpointercapture="endBoardPan"
      @dragover="onBoardDragOver"
    >
      <section
        v-for="column in store.boardColumns"
        :key="column.status"
        class="flex w-80 shrink-0 flex-col rounded-[12px] bg-white transition"
        :class="columnRingClass(column)"
        @dragover.prevent="onColumnDragOver(column, $event)"
        @dragleave="onColumnDragLeave(column)"
        @drop.prevent="onColumnDrop(column)"
      >
        <div
          class="flex items-center justify-between gap-3 rounded-t-[12px] px-4 py-3.5"
          :class="columnHeaderClass(column.status)"
        >
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900">
              {{ column.label }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ column.count }} ticket{{ column.count === 1 ? '' : 's' }}
            </p>
          </div>
          <span
            class="inline-flex h-7 min-w-7 items-center justify-center rounded-full px-2 text-xs font-semibold"
            :class="columnCountClass(column.status)"
          >
            {{ column.count }}
          </span>
        </div>

        <div class="min-h-[12rem] flex-1 space-y-3 p-3">
          <div v-if="!column.tickets?.length" class="px-2 py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No tickets</p>
            <p class="mt-1 text-xs text-slate-500">Drop a ticket here to update its status.</p>
          </div>
          <article
            v-for="ticket in column.tickets"
            :key="ticket.uuid"
            data-ticket-card
            :draggable="canMoveTickets"
            class="rounded-[12px] bg-white p-3.5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
            :class="[
              canMoveTickets ? 'cursor-grab' : 'cursor-pointer',
              draggingTicket?.uuid === ticket.uuid ? 'opacity-40' : '',
            ]"
            @pointerdown.stop
            @dragstart="onCardDragStart($event, ticket, column.status)"
            @dragend="onCardDragEnd"
            @click="onTicketActivate(ticket)"
          >
            <div class="mb-2 flex items-start justify-between gap-2">
              <p class="text-sm font-medium text-slate-900">{{ ticket.subject }}</p>
              <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            </div>
            <p class="text-xs text-slate-500">{{ ticket.ticket_number }}</p>
            <p class="mt-2 text-xs text-slate-500">
              {{ ticket.assignee?.full_name || 'Unassigned' }}
            </p>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { ArrowPathIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import { companyService } from '@/modules/companies/services/companyService';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const store = useSupportTicketsStore();
const toast = useToast();
const router = useRouter();
const { can } = usePermissions();
const companies = ref([]);
const company = ref('');
const boardRef = ref(null);
const isPanning = ref(false);
const didPan = ref(false);
const draggingTicket = ref(null);
const dropTargetStatus = ref(null);
const suppressTicketClick = ref(false);

const panState = {
  pointerId: null,
  startX: 0,
  startScrollLeft: 0,
};

const canMoveTickets = computed(() => can('support.update') && !store.saving);

const companyOptions = computed(() => [
  { value: '', label: 'All companies' },
  ...companies.value.map((item) => ({
    value: item.uuid,
    label: item.company_name,
  })),
]);

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
  store.error = null;
  store.successMessage = null;
  try {
    const { data } = await companyService.list({ per_page: 100 });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
  await loadBoard();
});

async function loadBoard() {
  try {
    await store.fetchBoard({ company: company.value || undefined });
  } catch {
    /* toast via store.error watcher */
  }
}

function onBoardPointerDown(event) {
  if (event.pointerType !== 'mouse' || event.button !== 0) {
    return;
  }
  if (event.target.closest('[data-ticket-card]')) {
    return;
  }

  const board = boardRef.value;
  if (!board || board.scrollWidth <= board.clientWidth) {
    return;
  }

  isPanning.value = true;
  didPan.value = false;
  panState.pointerId = event.pointerId;
  panState.startX = event.clientX;
  panState.startScrollLeft = board.scrollLeft;
  board.setPointerCapture(event.pointerId);
}

function onBoardPointerMove(event) {
  if (!isPanning.value || event.pointerId !== panState.pointerId) {
    return;
  }

  const board = boardRef.value;
  if (!board) {
    return;
  }

  const deltaX = event.clientX - panState.startX;
  if (Math.abs(deltaX) > 6) {
    didPan.value = true;
  }

  board.scrollLeft = panState.startScrollLeft - deltaX;
  event.preventDefault();
}

function endBoardPan(event) {
  if (event?.pointerId && event.pointerId !== panState.pointerId) {
    return;
  }

  const board = boardRef.value;
  if (board && panState.pointerId !== null && board.hasPointerCapture(panState.pointerId)) {
    board.releasePointerCapture(panState.pointerId);
  }

  isPanning.value = false;
  panState.pointerId = null;
}

function onBoardDragOver(event) {
  const board = boardRef.value;
  if (!board || !draggingTicket.value) {
    return;
  }

  const rect = board.getBoundingClientRect();
  const edge = 72;
  if (event.clientX < rect.left + edge) {
    board.scrollLeft -= 18;
  } else if (event.clientX > rect.right - edge) {
    board.scrollLeft += 18;
  }
}

function onCardDragStart(event, ticket, fromStatus) {
  if (!canMoveTickets.value) {
    event.preventDefault();
    return;
  }

  draggingTicket.value = { ...ticket, fromStatus };
  suppressTicketClick.value = true;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', ticket.uuid);
}

function onCardDragEnd() {
  draggingTicket.value = null;
  dropTargetStatus.value = null;
  window.setTimeout(() => {
    suppressTicketClick.value = false;
  }, 0);
}

function onColumnDragOver(column, event) {
  dropTargetStatus.value = column.status;
  event.dataTransfer.dropEffect = canDropOn(column) ? 'move' : 'none';
}

function onColumnDragLeave(column) {
  if (dropTargetStatus.value === column.status) {
    dropTargetStatus.value = null;
  }
}

async function onColumnDrop(column) {
  const ticket = draggingTicket.value;
  dropTargetStatus.value = null;
  draggingTicket.value = null;

  if (!ticket || ticket.fromStatus === column.status) {
    return;
  }

  if (!canDropOn(column, ticket)) {
    toast.error(`Cannot move this ticket to ${column.label}.`);
    return;
  }

  relocateTicket(ticket, ticket.fromStatus, column.status);

  try {
    await store.transitionTicket(ticket.uuid, {
      status: column.status,
      comments: 'Moved on kanban board',
    });
    await loadBoard();
  } catch {
    await loadBoard();
  }
}

function canDropOn(column, ticket = draggingTicket.value) {
  if (!ticket || !canMoveTickets.value) {
    return false;
  }
  if (ticket.fromStatus === column.status) {
    return true;
  }
  return (ticket.allowed_transitions || []).some((item) => item.value === column.status);
}

function columnRingClass(column) {
  if (!draggingTicket.value || dropTargetStatus.value !== column.status) {
    return 'ring-1 ring-zinc-100';
  }
  if (draggingTicket.value.fromStatus === column.status) {
    return 'ring-2 ring-brand-600';
  }
  return canDropOn(column)
    ? 'ring-2 ring-brand-600'
    : 'ring-2 ring-rose-400';
}

function relocateTicket(ticket, fromStatus, toStatus) {
  store.boardColumns = store.boardColumns.map((column) => {
    if (column.status === fromStatus) {
      const tickets = (column.tickets || []).filter((item) => item.uuid !== ticket.uuid);
      return { ...column, tickets, count: tickets.length };
    }
    if (column.status === toStatus) {
      const { fromStatus: _fromStatus, ...rest } = ticket;
      const moved = {
        ...rest,
        status: toStatus,
        status_label: column.label,
      };
      const tickets = [moved, ...(column.tickets || []).filter((item) => item.uuid !== ticket.uuid)];
      return { ...column, tickets, count: tickets.length };
    }
    return column;
  });
}

function onTicketActivate(ticket) {
  if (didPan.value || suppressTicketClick.value) {
    return;
  }

  router.push({ name: 'support.tickets.show', params: { id: ticket.uuid } });
}

function columnHeaderClass(status) {
  switch (status) {
    case 'open':
      return 'bg-sky-50';
    case 'pending':
      return 'bg-amber-50';
    case 'in_progress':
      return 'bg-indigo-50';
    case 'waiting_for_customer':
      return 'bg-violet-50';
    case 'resolved':
      return 'bg-emerald-50';
    case 'reopened':
      return 'bg-orange-50';
    case 'cancelled':
      return 'bg-rose-50';
    default:
      return 'bg-zinc-50';
  }
}

function columnCountClass(status) {
  switch (status) {
    case 'open':
      return 'bg-sky-100 text-sky-800';
    case 'pending':
      return 'bg-amber-100 text-amber-800';
    case 'in_progress':
      return 'bg-indigo-100 text-indigo-800';
    case 'waiting_for_customer':
      return 'bg-violet-100 text-violet-800';
    case 'resolved':
      return 'bg-emerald-100 text-emerald-800';
    case 'reopened':
      return 'bg-orange-100 text-orange-800';
    case 'cancelled':
      return 'bg-rose-100 text-rose-800';
    default:
      return 'bg-zinc-100 text-slate-600';
  }
}
</script>
