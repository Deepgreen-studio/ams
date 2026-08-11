<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="loading" class="space-y-3 px-6 py-5">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!items.length"
      title="No activity logs"
      description="Platform activity will appear here as users take actions."
      class="px-6 py-10"
    />

    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Module</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Description</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">User</th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Details</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in items"
            :key="item.id"
            class="border-b border-zinc-50 last:border-0 hover:bg-zinc-50/60"
          >
            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">
              {{ formatDate(item.created_at) }}
            </td>
            <td class="px-5 py-3.5">
              <StatusBadge :status="item.module || item.action || 'info'" />
            </td>
            <td class="px-5 py-3.5 text-slate-900">{{ item.description }}</td>
            <td class="px-5 py-3.5 text-slate-600">
              {{ item.user?.full_name || item.user?.email || '—' }}
            </td>
            <td class="px-5 py-3.5 text-right">
              <button
                type="button"
                class="text-sm font-medium text-brand-700 hover:underline"
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
