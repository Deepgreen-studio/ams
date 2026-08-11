<template>
  <div>
    <AuditTabs />

    <div class="space-y-4">
      <SearchFilters :model-value="store.filters" @submit="store.fetchList" @reset="onReset" />

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div v-if="store.loading" class="space-y-3 px-6 py-5">
          <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>

        <EmptyState
          v-else-if="!store.items.length"
          title="No system events"
          description="Operational events will appear here."
          class="px-6 py-10"
        />

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Event</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Module</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Level</th>
                <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Details</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in store.items"
                :key="item.uuid"
                class="border-b border-zinc-50 last:border-0 hover:bg-zinc-50/60"
              >
                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">
                  {{ formatDate(item.created_at) }}
                </td>
                <td class="px-5 py-3.5 text-slate-900">{{ item.event }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ item.module }}</td>
                <td class="px-5 py-3.5">
                  <StatusBadge :status="item.level" />
                </td>
                <td class="px-5 py-3.5 text-right">
                  <button
                    type="button"
                    class="text-sm font-medium text-brand-700 hover:underline"
                    @click="selected = item"
                  >
                    View
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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
