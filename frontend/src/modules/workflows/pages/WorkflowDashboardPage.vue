<template>
  <div>
    <PageHeader
      title="Workflow Engine"
      description="Design, monitor, and approve enterprise workflows."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'workflows.queue' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Approval queue
        </RouterLink>
        <RouterLink
          :to="{ name: 'workflows.designer.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New workflow
        </RouterLink>
      </template>
    </PageHeader>

    <WorkflowsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in definitionCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in instanceCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Workflow types</h2>
        <ul class="divide-y divide-slate-100">
          <li v-for="item in store.catalog.types" :key="item.value" class="flex items-center justify-between py-3">
            <span class="text-sm text-slate-800">{{ item.label }}</span>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ item.value }}</span>
          </li>
        </ul>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Step catalog</h2>
        <ul class="divide-y divide-slate-100">
          <li v-for="item in store.catalog.step_types" :key="item.value" class="py-3">
            <p class="text-sm font-medium text-slate-900">{{ item.label }}</p>
            <p class="text-xs text-slate-500">{{ item.value }}</p>
          </li>
        </ul>
      </div>
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

const definitionCards = computed(() => [
  { label: 'Definitions', value: store.statistics?.total ?? 0 },
  { label: 'Active', value: store.statistics?.active ?? 0 },
  { label: 'Draft', value: store.statistics?.draft ?? 0 },
  { label: 'Enabled', value: store.statistics?.enabled ?? 0 },
]);

const instanceCards = computed(() => [
  { label: 'Instances', value: store.instanceStatistics?.total ?? 0 },
  { label: 'In progress', value: store.instanceStatistics?.in_progress ?? 0 },
  { label: 'Approved', value: store.instanceStatistics?.approved ?? 0 },
  { label: 'Rejected', value: store.instanceStatistics?.rejected ?? 0 },
]);

onMounted(() => store.fetchDashboard());
</script>
