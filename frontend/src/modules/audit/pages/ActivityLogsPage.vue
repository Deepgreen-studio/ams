<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <ExportButton :loading="exporting" @click="onExport" />
    </Teleport>

    <AuditTabs />

    <div class="space-y-4">
      <SearchFilters :model-value="store.filters" @submit="store.fetchList" @reset="onReset" />

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
          <ActivityTable
            :items="store.items"
            :loading="store.loading"
            @select="selected = $event"
          />
          <div class="mt-4">
            <Pagination
              :meta="store.meta"
              :loading="store.loading"
              @change="(page) => store.fetchList({ page })"
            />
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h3 class="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
            Timeline
          </h3>
          <TimelineComponent :items="store.items.slice(0, 8)" />
        </div>
      </div>
    </div>

    <LogDetailsModal
      :open="Boolean(selected)"
      :item="selected"
      title="Activity details"
      :subtitle="selected?.description || ''"
      @close="selected = null"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ActivityTable from '@/modules/audit/components/ActivityTable.vue';
import AuditTabs from '@/modules/audit/components/AuditTabs.vue';
import ExportButton from '@/modules/audit/components/ExportButton.vue';
import LogDetailsModal from '@/modules/audit/components/LogDetailsModal.vue';
import SearchFilters from '@/modules/audit/components/SearchFilters.vue';
import TimelineComponent from '@/modules/audit/components/TimelineComponent.vue';
import { auditService } from '@/modules/audit/services/auditService';
import { useActivityStore } from '@/modules/audit/stores/audit';

const store = useActivityStore();
const selected = ref(null);
const exporting = ref(false);

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

async function onExport() {
  exporting.value = true;
  try {
    const params = Object.fromEntries(
      Object.entries(store.filters).filter(([, v]) => v !== '' && v != null),
    );
    const { data } = await auditService.exportActivityLogs(params);
    const url = URL.createObjectURL(data);
    const link = document.createElement('a');
    link.href = url;
    link.download = `activity-logs-${Date.now()}.csv`;
    link.click();
    URL.revokeObjectURL(url);
  } finally {
    exporting.value = false;
  }
}
</script>
