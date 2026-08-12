<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'workflows.monitor' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to monitor
      </RouterLink>
    </Teleport>

    <WorkflowsSubnav />

    <div v-if="store.loading && !instance" class="space-y-4">
      <div class="h-48 animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="h-64 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <template v-else-if="instance">
      <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8 lg:col-span-2">
          <div class="mb-5">
            <h2 class="text-base font-semibold text-slate-900">
              {{ instance.subject_label || 'Workflow instance' }}
            </h2>
            <p class="mt-0.5 text-sm text-slate-500">
              Current stage, due date, and approval actions for this run.
            </p>
          </div>

          <dl class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 ring-1 ring-zinc-100">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Workflow</dt>
              <dd class="mt-1 text-sm font-semibold text-slate-900">
                {{ instance.workflow?.name || '—' }}
              </dd>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 ring-1 ring-zinc-100">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
              <dd class="mt-1.5">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="statusBadgeClass(instance.status)"
                >
                  {{ instance.status_label || instance.status }}
                </span>
              </dd>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 ring-1 ring-zinc-100">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Current stage</dt>
              <dd class="mt-1 text-sm font-semibold text-slate-900">
                {{ instance.current_step?.name || '—' }}
              </dd>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 ring-1 ring-zinc-100">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Due</dt>
              <dd class="mt-1 text-sm font-semibold text-slate-900">
                {{ formatDate(instance.due_at) }}
              </dd>
            </div>
          </dl>

          <div
            v-if="canDecide"
            class="mt-6 flex flex-col gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center"
          >
            <input
              v-model="comment"
              type="text"
              placeholder="Decision comment"
              class="h-10 min-w-0 flex-1 rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                class="h-10 rounded-[12px] bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
                :disabled="store.saving"
                @click="approve"
              >
                Approve
              </button>
              <button
                type="button"
                class="h-10 rounded-[12px] bg-rose-600 px-5 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
                :disabled="store.saving"
                @click="reject"
              >
                Reject
              </button>
            </div>
          </div>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">Pending approvers</h3>
          <p class="mt-0.5 text-sm text-slate-500">Who can decide on the current stage.</p>

          <ul v-if="instance.pending_approvers?.length" class="mt-5 space-y-2.5">
            <li
              v-for="(item, index) in instance.pending_approvers"
              :key="index"
              class="flex items-center justify-between gap-3 rounded-[12px] bg-zinc-50 px-3.5 py-3 ring-1 ring-zinc-100"
            >
              <span class="text-sm font-medium text-slate-900">{{ item.value }}</span>
              <span class="shrink-0 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                {{ item.type }}
              </span>
            </li>
          </ul>
          <p v-else class="mt-8 text-center text-sm text-slate-500">
            No pending approvers on this stage.
          </p>
        </section>
      </div>

      <WorkflowTimeline :logs="instance.logs || []" :loading="store.loading" />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useToast } from '@/composables/useToast';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import WorkflowTimeline from '@/modules/workflows/components/WorkflowTimeline.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const route = useRoute();
const toast = useToast();
const comment = ref('');

const instance = computed(() => store.currentInstance);

const canDecide = computed(() => {
  const status = instance.value?.status;
  return status === 'pending' || status === 'in_progress';
});

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

function statusBadgeClass(status) {
  if (status === 'approved' || status === 'completed') return 'bg-emerald-50 text-emerald-700';
  if (status === 'rejected' || status === 'cancelled' || status === 'timed_out') {
    return 'bg-rose-50 text-rose-700';
  }
  if (status === 'in_progress' || status === 'pending') return 'bg-brand-50 text-brand-700';
  return 'bg-zinc-100 text-slate-700';
}

async function approve() {
  await store.approveInstance(route.params.id, comment.value);
  comment.value = '';
}

async function reject() {
  await store.rejectInstance(route.params.id, comment.value);
  comment.value = '';
}

onMounted(() => {
  if (store.successMessage) {
    toast.success(store.successMessage);
    store.successMessage = null;
  }
  store.fetchInstance(route.params.id);
});
</script>
