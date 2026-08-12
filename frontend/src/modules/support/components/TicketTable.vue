<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!tickets.length"
      title="No support tickets found"
      description="Create your first support ticket to get started."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Ticket</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Category</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Priority</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Assignee</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 xl:table-cell">Company</th>
            <th
              v-if="hasAnyAction"
              class="px-4 py-3 text-right font-semibold text-slate-600"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="ticket in tickets" :key="ticket.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ ticket.subject }}</p>
              <p class="text-xs text-slate-500">{{ ticket.ticket_number }}</p>
            </td>
            <td class="hidden px-4 py-3 md:table-cell">
              <TicketCategoryBadge :category="ticket.category" :label="ticket.category_label" />
            </td>
            <td class="px-4 py-3">
              <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            </td>
            <td class="px-4 py-3">
              <TicketStatusBadge :status="ticket.status" :label="ticket.status_label" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ ticket.assignee?.full_name || 'Unassigned' }}
            </td>
            <td class="hidden px-4 py-3 text-slate-600 xl:table-cell">
              {{ ticket.company?.company_name || '—' }}
            </td>
            <td v-if="hasAnyAction" class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  v-if="can('support.view')"
                  :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <button
                  v-if="can('support.delete')"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('archive', ticket)"
                >
                  Archive
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import TicketCategoryBadge from '@/modules/support/components/TicketCategoryBadge.vue';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import TicketStatusBadge from '@/modules/support/components/TicketStatusBadge.vue';

defineProps({
  tickets: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['archive']);

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() => canAny('support.view', 'support.delete'));
</script>
