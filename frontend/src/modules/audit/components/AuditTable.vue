<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <EmptyState
      v-if="!loading && !items.length"
      title="No audit entries"
      description="Before/after change trails will appear here."
    />
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Module</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Changed</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Details</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in items" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at) }}</td>
            <td class="px-4 py-3 text-slate-900">{{ item.module }}</td>
            <td class="px-4 py-3"><StatusBadge :status="item.action" /></td>
            <td class="px-4 py-3 text-slate-600">
              {{ (item.changed_fields || []).join(',') || '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="text-xs font-medium text-brand-700 hover:underline"
                @click="$emit('select', item)"
              >
                View
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
import StatusBadge from '@/modules/audit/components/StatusBadge.vue';

defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['select']);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
