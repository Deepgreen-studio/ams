<template>
  <div :class="embedded ? '' : 'rounded-[12px] bg-white ring-1 ring-zinc-100'">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!items.length"
      :title="emptyTitle"
      :description="emptyDescription"
      class="px-8 py-6"
    >
      <template v-if="$slots['empty-action']" #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
          <tr class="border-b border-zinc-100">
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 text-left text-sm font-semibold text-slate-600"
            >
              {{ column.label }}
            </th>
            <th class="px-6 py-4 text-right text-sm font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in items"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 text-slate-700"
            >
              <slot :name="`cell-${column.key}`" :item="item">
                <span
                  :class="
                    column.key === 'name' || column.key === 'branch_name'
                      ? 'font-semibold text-slate-900'
                      : ''
                  "
                >
                  {{ item[column.key] || '-' }}
                </span>
              </slot>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-1">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-[12px] px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-zinc-100"
                  @click="$emit('edit', item)"
                >
                  <PencilSquareIcon class="h-4 w-4 text-slate-500" />
                  Edit
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-[12px] px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                  @click="$emit('delete', item)"
                >
                  <TrashIcon class="h-4 w-4" />
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-6 py-5 sm:px-8">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  items: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  embedded: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'No records' },
  emptyDescription: { type: String, default: 'Nothing to display yet.' },
});

defineEmits(['edit', 'delete']);
</script>
