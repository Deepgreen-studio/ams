<template>
  <div>
    <PageHeader
      :title="store.currentInstance?.subject_label || 'Workflow Instance'"
      description="Timeline, current stage, and approval actions."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'workflows.monitor' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to monitor
        </RouterLink>
      </template>
    </PageHeader>

    <WorkflowsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !instance" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <template v-else-if="instance">
      <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
          <dl class="grid gap-4 sm:grid-cols-2 text-sm">
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Workflow</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ instance.workflow?.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ instance.status_label || instance.status }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Current stage</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ instance.current_step?.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Due</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ formatDate(instance.due_at) }}</dd>
            </div>
          </dl>

          <div v-if="canDecide" class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
            <input
              v-model="comment"
              type="text"
              placeholder="Decision comment"
              class="min-w-[220px] flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
            <button
              type="button"
              class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
              :disabled="store.saving"
              @click="approve"
            >
              Approve
            </button>
            <button
              type="button"
              class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
              :disabled="store.saving"
              @click="reject"
            >
              Reject
            </button>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Pending approvers</h3>
          <ul v-if="instance.pending_approvers?.length" class="space-y-2 text-sm text-slate-700">
            <li v-for="(item, index) in instance.pending_approvers" :key="index">
              {{ item.type }}: {{ item.value }}
            </li>
          </ul>
          <p v-else class="text-sm text-slate-500">No pending approvers on this stage.</p>
        </div>
      </div>

      <WorkflowTimeline :logs="instance.logs || []" :loading="store.loading" />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import WorkflowTimeline from '@/modules/workflows/components/WorkflowTimeline.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const route = useRoute();
const comment = ref('');

const instance = computed(() => store.currentInstance);

const canDecide = computed(() => {
  const status = instance.value?.status;
  return status === 'pending' || status === 'in_progress';
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function approve() {
  await store.approveInstance(route.params.id, comment.value);
  comment.value = '';
}

async function reject() {
  await store.rejectInstance(route.params.id, comment.value);
  comment.value = '';
}

onMounted(() => store.fetchInstance(route.params.id));
</script>
