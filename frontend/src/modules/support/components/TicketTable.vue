<template>
  <div :class="framed ? 'overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100' : ''">
    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <EmptyState
      v-else-if="!tickets.length"
      title="No support tickets found"
      description="Try adjusting your filters or create a new support ticket."
      class="px-6 py-10 sm:px-8"
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>
    <div v-else class="scrollbar-light overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Ticket</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Category
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Priority</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Assignee
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 xl:table-cell">
              Company
            </th>
            <th
              v-if="hasAnyAction"
              class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="ticket in tickets"
            :key="ticket.uuid"
            class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
          >
            <td class="px-5 py-4">
              <div class="flex flex-wrap items-center gap-2">
                <RouterLink
                  v-if="can('support.view')"
                  :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                  class="font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ ticket.subject }}
                </RouterLink>
                <p v-else class="font-medium text-slate-900">{{ ticket.subject }}</p>
                <span
                  v-if="ticket.source === 'sms'"
                  class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-sky-100"
                >
                  SMS
                </span>
              </div>
              <p class="mt-0.5 text-xs text-slate-500">{{ ticket.ticket_number }}</p>
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <TicketCategoryBadge :category="ticket.category" :label="ticket.category_label" />
            </td>
            <td class="px-5 py-4">
              <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            </td>
            <td class="px-5 py-4">
              <TicketStatusBadge :status="ticket.status" :label="ticket.status_label" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ ticket.assignee?.full_name || 'Unassigned' }}
            </td>
            <td class="hidden px-5 py-4 text-slate-600 xl:table-cell">
              {{ ticket.company?.company_name || '—' }}
            </td>
            <td v-if="hasAnyAction" class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === ticket.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(ticket.uuid, $event)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <Teleport to="body">
    <div
      v-if="openMenuId && activeTicket"
      class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
      role="menu"
      :style="menuStyle"
      @click.stop
    >
      <RouterLink
        v-if="can('support.view')"
        :to="{ name: 'support.tickets.show', params: { id: activeTicket.uuid } }"
        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
        role="menuitem"
        @click="closeMenu"
      >
        <EyeIcon class="h-4 w-4 text-slate-400" />
        View
      </RouterLink>
      <RouterLink
        v-if="can('support.update')"
        :to="{ name: 'support.tickets.edit', params: { id: activeTicket.uuid } }"
        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
        role="menuitem"
        @click="closeMenu"
      >
        <PencilSquareIcon class="h-4 w-4 text-slate-400" />
        Edit
      </RouterLink>
      <button
        v-if="can('support.delete')"
        type="button"
        class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
        role="menuitem"
        @click="onArchive(activeTicket)"
      >
        <ArchiveBoxIcon class="h-4 w-4 text-red-500" />
        Archive
      </button>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { ArchiveBoxIcon, EllipsisVerticalIcon, EyeIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import TicketCategoryBadge from '@/modules/support/components/TicketCategoryBadge.vue';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import TicketStatusBadge from '@/modules/support/components/TicketStatusBadge.vue';

const props = defineProps({
  tickets: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  framed: { type: Boolean, default: true },
});

const emit = defineEmits(['archive']);

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() => canAny('support.view', 'support.update', 'support.delete'));
const openMenuId = ref(null);
const menuStyle = ref({});

const activeTicket = computed(
  () => props.tickets.find((ticket) => ticket.uuid === openMenuId.value) || null,
);

onMounted(() => {
  document.addEventListener('click', closeMenu);
  window.addEventListener('scroll', closeMenu, true);
  window.addEventListener('resize', closeMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu);
  window.removeEventListener('scroll', closeMenu, true);
  window.removeEventListener('resize', closeMenu);
});

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 8 + 3 * 36;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  openMenuId.value = id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onArchive(ticket) {
  closeMenu();
  emit('archive', ticket);
}
</script>
