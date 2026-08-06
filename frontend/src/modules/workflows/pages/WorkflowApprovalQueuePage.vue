<template>
  <div>
    <PageHeader
      title="Approval Queue"
      description="Workflow instances waiting for your approval decision."
    />
    <WorkflowsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Subject</th>
            <th class="px-4 py-3">Workflow</th>
            <th class="px-4 py-3">Stage</th>
            <th class="px-4 py-3">Due</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading queue...</td>
          </tr>
          <tr v-else-if="!store.queue.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Queue is clear.</td>
          </tr>
          <tr v-for="item in store.queue" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.subject_label || 'Untitled' }}</p>
              <p class="text-xs text-slate-500">{{ item.subject_type || '—' }}</p>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.workflow?.name || '—' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ item.current_step?.name || '—' }}</td>
            <td class="px-4 py-3 text-slate-500">{{ formatDate(item.due_at) }}</td>
            <td class="px-4 py-3 text-right space-x-2">
              <RouterLink
                :to="{ name: 'workflows.instances.show', params: { id: item.uuid } }"
                class="text-sm font-medium text-brand-700 hover:underline"
              >
                Open
              </RouterLink>
              <button
                type="button"
                class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700"
                :disabled="store.saving"
                @click="approve(item)"
              >
                Approve
              </button>
              <button
                type="button"
                class="rounded-lg bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700"
                :disabled="store.saving"
                @click="reject(item)"
              >
                Reject
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function approve(item) {
  const comment = window.prompt('Approval comment (optional):', '') ?? '';
  await store.approveInstance(item.uuid, comment);
}

async function reject(item) {
  const comment = window.prompt('Rejection reason (optional):', '') ?? '';
  await store.rejectInstance(item.uuid, comment);
}

onMounted(() => store.fetchQueue());
</script>
