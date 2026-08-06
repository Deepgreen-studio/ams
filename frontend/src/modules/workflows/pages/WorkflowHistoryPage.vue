<template>
  <div>
    <PageHeader
      title="Workflow History"
      description="Global audit trail of workflow actions across the platform."
    />
    <WorkflowsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="filters.action" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All actions</option>
        <option value="started">Started</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="escalated">Escalated</option>
        <option value="timed_out">Timed out</option>
        <option value="completed">Completed</option>
      </select>
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search comments..."
        class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" @click="load">
        Apply
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">When</th>
            <th class="px-4 py-3">Workflow</th>
            <th class="px-4 py-3">Action</th>
            <th class="px-4 py-3">Actor</th>
            <th class="px-4 py-3">Comment</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading...</td>
          </tr>
          <tr v-else-if="!store.logs.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No history yet.</td>
          </tr>
          <tr v-for="log in store.logs" :key="log.uuid">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(log.created_at) }}</td>
            <td class="px-4 py-3 text-slate-800">
              {{ log.instance?.workflow?.name || log.instance?.subject_label || '—' }}
            </td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                {{ log.action_label || log.action }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ log.actor?.full_name || 'System' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ log.comment || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const filters = reactive({ action: '', search: '' });

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function load() {
  await store.fetchHistory({ ...filters });
}

onMounted(load);
</script>
