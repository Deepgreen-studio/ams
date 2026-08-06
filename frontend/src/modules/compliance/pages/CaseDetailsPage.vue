<template>
  <div>
    <PageHeader
      :title="current?.title || 'Case details'"
      description="Compliance case profile and governance details."
    >
      <template #actions>
        <template v-if="current">
          <RouterLink
            :to="{ name: 'compliance.cases.edit', params: { id: current.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="current.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="store.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
          </button>
        </template>
      </template>
    </PageHeader>

    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !current" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="current" class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="mb-4 flex flex-wrap items-center gap-2">
          <CaseStatusBadge :status="current.status" :label="current.status_label" />
          <CasePriorityBadge :priority="current.priority" :label="current.priority_label" />
          <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
            {{ current.case_type_label || current.case_type }}
          </span>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Case number</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ current.case_number }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Company</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ current.company?.company_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Assignee</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ current.assignee?.full_name || 'Unassigned' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Due date</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ current.due_date || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Completed at</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ current.completed_at ? formatDate(current.completed_at) : '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created by</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ current.creator?.full_name || '—' }}</dd>
          </div>
        </dl>

        <div class="mt-6 border-t border-slate-100 pt-4">
          <h2 class="text-sm font-semibold text-slate-900">Description</h2>
          <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">
            {{ current.description || 'No description provided.' }}
          </p>
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete compliance case"
      :message="`Soft delete ${current?.title || 'this case'}?`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import CasePriorityBadge from '@/modules/compliance/components/CasePriorityBadge.vue';
import CaseStatusBadge from '@/modules/compliance/components/CaseStatusBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useComplianceStore } from '@/modules/compliance/stores/compliance';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';

const route = useRoute();
const router = useRouter();
const store = useComplianceStore();
const showDelete = ref(false);

const current = computed(() => store.currentCase);

onMounted(() => {
  store.fetchCase(route.params.id);
});

function formatDate(value) {
  try {
    return new Date(value).toLocaleString();
  } catch {
    return value;
  }
}

async function confirmDelete() {
  await store.deleteCase(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'compliance.cases.index' });
}

async function restore() {
  await store.restoreCase(route.params.id);
  await store.fetchCase(route.params.id);
}
</script>
