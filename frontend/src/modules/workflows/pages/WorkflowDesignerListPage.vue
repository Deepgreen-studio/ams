<template>
  <div>
    <!-- <PageHeader
      title="Workflow Designer"
      description="Create and manage workflow definitions with a visual stage builder."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'workflows.designer.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create workflow
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'workflows.designer.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create workflow
        </RouterLink>
    </Teleport>

    <WorkflowsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search workflows..."
        class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <select v-model="filters.type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All types</option>
        <option v-for="item in store.catalog.types" :key="item.value" :value="item.value">{{ item.label }}</option>
      </select>
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All statuses</option>
        <option v-for="item in store.catalog.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
      </select>
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" @click="load">
        Apply
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Workflow</th>
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Steps</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading...</td>
          </tr>
          <tr v-else-if="!store.workflows.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No workflows yet.</td>
          </tr>
          <tr v-for="item in store.workflows" :key="item.uuid" class="hover:bg-slate-50/70">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.description || 'No description' }}</p>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.type_label || item.type }}</td>
            <td class="px-4 py-3">
              <button
                type="button"
                class="rounded-full px-2.5 py-1 text-xs font-medium"
                :class="item.is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                @click="store.toggleWorkflow(item.uuid, !item.is_enabled)"
              >
                {{ item.is_enabled ? 'Enabled' : 'Disabled' }} · {{ item.status }}
              </button>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ item.steps?.length || 0 }}</td>
            <td class="px-4 py-3 text-right space-x-3">
              <RouterLink
                :to="{ name: 'workflows.designer.edit', params: { id: item.uuid } }"
                class="text-sm font-medium text-brand-700 hover:underline"
              >
                Edit
              </RouterLink>
              <button type="button" class="text-sm font-medium text-slate-700 hover:underline" @click="start(item)">
                Start
              </button>
              <button type="button" class="text-sm font-medium text-rose-600 hover:underline" @click="remove(item)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const router = useRouter();
const filters = reactive({ search: '', type: '', status: '' });

async function load() {
  await store.fetchWorkflows({ ...filters });
}

async function start(item) {
  const instance = await store.startWorkflow(item.uuid, {
    subject_type: 'manual',
    subject_label: `${item.name} run`,
    context: { compliance_ready: '1' },
  });
  if (instance?.uuid) {
    await router.push({ name: 'workflows.instances.show', params: { id: instance.uuid } });
  }
}

async function remove(item) {
  if (!window.confirm(`Delete workflow "${item.name}"?`)) return;
  await store.deleteWorkflow(item.uuid);
}

onMounted(load);
</script>
