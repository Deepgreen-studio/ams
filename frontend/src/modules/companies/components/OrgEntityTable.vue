<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <EmptyState
      v-if="!loading && !items.length"
      :title="emptyTitle"
      :description="emptyDescription"
    />
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 text-left font-semibold text-slate-600"
            >
              {{ column.label }}
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in items" :key="item.uuid" class="hover:bg-slate-50/80">
            <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-slate-700">
              <slot :name="`cell-${column.key}`" :item="item">{{ item[column.key] || '—' }}</slot>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                @click="$emit('delete', item)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  items: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'No records' },
  emptyDescription: { type: String, default: 'Nothing to display yet.' },
});
defineEmits(['delete']);
</script>
