<template>
  <div>
    <PageHeader
      title="System events"
      description="Internal platform lifecycle and operational events."
    />
    <AuditTabs />
    <div class="mt-4 space-y-4">
      <SearchFilters :model-value="store.filters" @submit="store.fetchList" @reset="onReset" />
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <EmptyState
          v-if="!store.loading && !store.items.length"
          title="No system events"
          description="Operational events will appear here."
        />
        <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Event</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Module</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Level</th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Details</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in store.items" :key="item.uuid" class="hover:bg-slate-50/80">
              <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at) }}</td>
              <td class="px-4 py-3 text-slate-900">{{ item.event }}</td>
              <td class="px-4 py-3 text-slate-600">{{ item.module }}</td>
              <td class="px-4 py-3"><StatusBadge :status="item.level" /></td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="text-xs font-medium text-brand-700 hover:underline"
                  @click="selected = item"
                >
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination
        :meta="store.meta"
        :loading="store.loading"
        @change="(page) => store.fetchList({ page })"
      />
    </div>
    <LogDetailsModal
      :open="Boolean(selected)"
      :item="selected"
      title="System event"
      :subtitle="selected?.event || ''"
      @close="selected = null"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import AuditTabs from '@/modules/audit/components/AuditTabs.vue';
import LogDetailsModal from '@/modules/audit/components/LogDetailsModal.vue';
import SearchFilters from '@/modules/audit/components/SearchFilters.vue';
import StatusBadge from '@/modules/audit/components/StatusBadge.vue';
import { useSystemEventsStore } from '@/modules/audit/stores/audit';

const store = useSystemEventsStore();
const selected = ref(null);
onMounted(() => store.fetchList());
function onReset() {
  store.filters = {
    search: '',
    module: '',
    action: '',
    date_from: '',
    date_to: '',
    per_page: 15,
    page: 1,
  };
  store.fetchList();
}
function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
