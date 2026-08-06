<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <EmptyState
      v-if="!loading && !items.length"
      title="No activity logs"
      description="Platform activity will appear here as users take actions."
    />
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Module</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">User</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Details</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/80">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at) }}</td>
            <td class="px-4 py-3">
              <StatusBadge :status="item.module || item.action || 'info'" />
            </td>
            <td class="px-4 py-3 text-slate-900">{{ item.description }}</td>
            <td class="px-4 py-3 text-slate-600">
              {{ item.user?.full_name || item.user?.email || '—' }}
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
