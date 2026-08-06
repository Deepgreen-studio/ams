<template>
  <div>
    <PageHeader
      :title="assignment?.application?.name || 'Assignment details'"
      description="Assignment profile and activity timeline."
    >
      <template #actions>
        <template v-if="assignment">
          <RouterLink
            :to="{ name: 'customers.applications', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back
          </RouterLink>
          <RouterLink
            :to="{
              name: 'customers.applications.edit',
              params: { id: route.params.id, assignmentId: assignment.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="assignment.deleted_at"
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
            @click="showArchive = true"
          >
            Archive
          </button>
        </template>
      </template>
    </PageHeader>

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !assignment" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="assignment" class="space-y-6">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ assignment.application?.name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ assignment.customer?.display_name }}</p>
          </div>
          <AssignmentStatusBadge :status="assignment.status" />
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Ownership</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ formatOwnership(assignment.ownership_type) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Environment</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ assignment.environment?.name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Integration</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ assignment.integration?.name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
              Owner contact
            </dt>
            <dd class="mt-1 text-sm text-slate-900">{{ assignment.owner_contact?.name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Activated</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ formatDate(assignment.activated_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Expires</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ formatDate(assignment.expires_at) }}</dd>
          </div>
          <div class="sm:col-span-2 lg:col-span-3">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Notes</dt>
            <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-900">
              {{ assignment.notes || '—' }}
            </dd>
          </div>
        </dl>
      </div>

      <AssignmentTimeline :items="store.timeline" :loading="timelineLoading" />
    </div>

    <DeleteConfirmation
      :open="showArchive"
      title="Archive assignment"
      :message="`Archive ${assignment?.application?.name || 'this assignment'}?`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="showArchive = false"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import AssignmentStatusBadge from '@/modules/customers/components/AssignmentStatusBadge.vue';
import AssignmentTimeline from '@/modules/customers/components/AssignmentTimeline.vue';
import { useCustomerApplicationsStore } from '@/modules/customers/stores/applications';

const route = useRoute();
const router = useRouter();
const store = useCustomerApplicationsStore();
const showArchive = ref(false);
const timelineLoading = ref(false);

const assignment = computed(() => store.currentAssignment);

onMounted(async () => {
  await store.fetchAssignment(route.params.assignmentId);
  timelineLoading.value = true;
  try {
    await store.fetchTimeline(route.params.assignmentId);
  } finally {
    timelineLoading.value = false;
  }
});

function formatOwnership(value) {
  return (value || '').replaceAll('_', '').replace(/\b\w/g, (c) => c.toUpperCase()) || '—';
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function confirmArchive() {
  await store.archiveAssignment(route.params.assignmentId);
  showArchive.value = false;
  await router.push({ name: 'customers.applications', params: { id: route.params.id } });
}

async function restore() {
  await store.restoreAssignment(route.params.assignmentId);
  await store.fetchAssignment(route.params.assignmentId);
  await store.fetchTimeline(route.params.assignmentId);
}
</script>
