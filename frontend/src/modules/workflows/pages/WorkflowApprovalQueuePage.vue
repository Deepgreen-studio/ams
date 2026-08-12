<template>
  <div>
    <WorkflowsSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <h2 class="text-base font-semibold text-slate-900">Approval queue</h2>
        <p class="mt-1 text-sm text-slate-500">
          Workflow instances waiting for your approval decision.
        </p>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 5" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.queue.length"
        title="Queue is clear"
        description="There are no workflow instances waiting for your approval right now."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <RouterLink
            :to="{ name: 'workflows.monitor' }"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          >
            View monitor
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subject</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Workflow</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Stage</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Due</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.queue"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.subject_label || 'Untitled' }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.subject_type || '—' }}</p>
              </td>
              <td class="px-5 py-4 text-slate-700">{{ item.workflow?.name || '—' }}</td>
              <td class="px-5 py-4 text-slate-600">{{ item.current_step?.name || '—' }}</td>
              <td class="px-5 py-4 text-slate-500">{{ formatDate(item.due_at) }}</td>
              <td class="px-5 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <RouterLink
                    :to="{ name: 'workflows.instances.show', params: { id: item.uuid } }"
                    class="text-sm font-medium text-brand-700 hover:underline"
                  >
                    Open
                  </RouterLink>
                  <button
                    type="button"
                    class="rounded-[10px] bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="store.saving"
                    @click="approve(item)"
                  >
                    Approve
                  </button>
                  <button
                    type="button"
                    class="rounded-[10px] bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700 disabled:opacity-50"
                    :disabled="store.saving"
                    @click="reject(item)"
                  >
                    Reject
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const toast = useToast();

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

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
