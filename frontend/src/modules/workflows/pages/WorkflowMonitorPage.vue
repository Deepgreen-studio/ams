<template>
  <div>
    <!-- <PageHeader
      title="Workflow Monitor"
      description="Live view of running and recently completed workflow instances."
    /> -->
    <WorkflowsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Subject</th>
            <th class="px-4 py-3">Workflow</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Current stage</th>
            <th class="px-4 py-3 text-right">Open</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading...</td>
          </tr>
          <tr v-else-if="!store.monitorRecent.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No instances yet.</td>
          </tr>
          <tr v-for="item in store.monitorRecent" :key="item.uuid">
            <td class="px-4 py-3 font-medium text-slate-900">{{ item.subject_label || '—' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ item.workflow?.name || '—' }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                {{ item.status_label || item.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ item.current_step?.name || '—' }}</td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{ name: 'workflows.instances.show', params: { id: item.uuid } }"
                class="text-sm font-medium text-brand-700 hover:underline"
              >
                View
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();

const cards = computed(() => [
  { label: 'Total', value: store.instanceStatistics?.total ?? 0 },
  { label: 'In progress', value: store.instanceStatistics?.in_progress ?? 0 },
  { label: 'Approved', value: store.instanceStatistics?.approved ?? 0 },
  { label: 'Timed out', value: store.instanceStatistics?.timed_out ?? 0 },
]);

onMounted(() => store.fetchMonitor());
</script>
