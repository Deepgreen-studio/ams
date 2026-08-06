<template>
  <div>
    <PageHeader
      title="Configuration history"
      description="Versioned snapshots of this configuration. Restore any previous version."
    >
      <template #actions>
        <RouterLink
          :to="{
            name: 'applications.configurations.edit',
            params: { id: route.params.id, configurationId: route.params.configurationId },
          }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to editor
        </RouterLink>
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="configurationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ configurationsStore.error }}
    </div>
    <div
      v-if="configurationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ configurationsStore.successMessage }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="configurationsStore.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!configurationsStore.history.length"
        title="No history"
        description="Configuration changes will appear here after updates."
      />
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Version</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Summary</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
                Status
              </th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
                Changed
              </th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in configurationsStore.history" :key="item.uuid">
              <td class="px-4 py-3 font-medium text-slate-900">v{{ item.version }}</td>
              <td class="px-4 py-3 text-slate-600">{{ item.change_summary || '—' }}</td>
              <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
                {{ item.status || '—' }}
              </td>
              <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
                {{ formatDate(item.created_at) }}
                <span class="block text-xs text-slate-400">{{
                  item.creator?.full_name || '—'
                }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                  :disabled="configurationsStore.saving"
                  @click="restore(item)"
                >
                  Restore
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';

const route = useRoute();
const configurationsStore = useConfigurationsStore();

onMounted(() => {
  configurationsStore.fetchHistory(route.params.id, route.params.configurationId);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function restore(item) {
  await configurationsStore.restoreHistory(
    route.params.id,
    route.params.configurationId,
    item.uuid,
  );
}
</script>
