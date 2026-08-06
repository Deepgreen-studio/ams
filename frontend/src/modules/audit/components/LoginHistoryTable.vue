<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <EmptyState
      v-if="!loading && !items.length"
      title="No login history"
      description="Successful and failed sign-ins will appear here."
    />
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Login</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">User</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Device</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">IP</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in items" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.login_at) }}</td>
            <td class="px-4 py-3 text-slate-900">
              {{ item.user?.full_name || item.user?.email || '—' }}
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ item.browser }} · {{ item.operating_system }} · {{ item.device }}
            </td>
            <td class="px-4 py-3 text-slate-600">{{ item.ip_address || '—' }}</td>
            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
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

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
