<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <template v-if="assignment">
        <RouterLink
          :to="{ name: 'customers.applications', params: { id: route.params.id } }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="openEdit"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </button>
        <button
          v-if="assignment.deleted_at"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="restore"
        >
          Restore
        </button>
        <button
          v-else
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Delete
        </button>
      </template>
    </Teleport>

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div
      v-if="store.loading && !assignment"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="assignment" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <div class="flex flex-wrap items-start gap-4">
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[14px] bg-brand-50 text-base font-semibold text-brand-700"
            >
              {{ initials(assignment.application?.name) }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h2 class="text-xl font-semibold text-slate-900">
                    {{ assignment.application?.name || 'Assignment' }}
                  </h2>
                  <p class="mt-1 text-sm text-slate-500">
                    {{ assignment.customer?.display_name || '—' }}
                  </p>
                </div>
                <AssignmentStatusBadge :status="assignment.status" />
              </div>
              <p
                v-if="assignment.integration?.name || assignment.application?.platform"
                class="mt-3 text-sm text-slate-600"
              >
                {{ assignment.integration?.name || assignment.application?.platform }}
              </p>
            </div>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">Assignment details</h3>
          <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <div
              v-for="item in detailItems"
              :key="item.label"
              class="grid grid-cols-[8.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[10rem_1fr]"
            >
              <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
              <dd class="text-sm font-medium text-slate-900 whitespace-pre-wrap">
                {{ item.value }}
              </dd>
            </div>
          </dl>
        </div>

        <AssignmentTimeline :items="store.timeline" :loading="timelineLoading" />
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Status</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Status</dt>
              <dd><AssignmentStatusBadge :status="assignment.status" /></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Ownership</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatOwnership(assignment.ownership_type) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Environment</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ assignment.environment?.name || '—' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Activated</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(assignment.activated_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Expires</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(assignment.expires_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Record</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Created</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(assignment.created_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Updated</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(assignment.updated_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Deleted</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ assignment.deleted_at ? formatDate(assignment.deleted_at) : '—' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <AssignmentFormModal
      :open="formOpen"
      :loading="store.saving"
      :assignment="assignment"
      :customer-id="route.params.id"
      :company-id="companyId"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="showDelete"
      title="Delete assignment"
      :message="`Soft delete ${assignment?.application?.name || 'this assignment'}? It can be restored later.`"
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
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import AssignmentFormModal from '@/modules/customers/components/AssignmentFormModal.vue';
import AssignmentStatusBadge from '@/modules/customers/components/AssignmentStatusBadge.vue';
import AssignmentTimeline from '@/modules/customers/components/AssignmentTimeline.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCustomerApplicationsStore } from '@/modules/customers/stores/applications';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const store = useCustomerApplicationsStore();
const showDelete = ref(false);
const formOpen = ref(false);
const timelineLoading = ref(false);

const assignment = computed(() => store.currentAssignment);
const companyId = computed(
  () =>
    customersStore.currentCustomer?.company?.uuid ||
    assignment.value?.customer?.company?.uuid ||
    '',
);

const detailItems = computed(() => [
  { label: 'Ownership', value: formatOwnership(assignment.value?.ownership_type) },
  { label: 'Environment', value: assignment.value?.environment?.name || '—' },
  { label: 'Integration', value: assignment.value?.integration?.name || '—' },
  { label: 'Owner contact', value: assignment.value?.owner_contact?.name || '—' },
  { label: 'Activated', value: formatDate(assignment.value?.activated_at) },
  { label: 'Expires', value: formatDate(assignment.value?.expires_at) },
  { label: 'Notes', value: assignment.value?.notes || '—' },
]);

onMounted(async () => {
  await Promise.all([
    customersStore.fetchCustomer(route.params.id),
    store.fetchAssignment(route.params.assignmentId),
  ]);
  timelineLoading.value = true;
  try {
    await store.fetchTimeline(route.params.assignmentId);
  } finally {
    timelineLoading.value = false;
  }
});

function initials(name) {
  return String(name || 'A')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function formatOwnership(value) {
  return (value || '').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()) || '—';
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function openEdit() {
  store.clearMessages();
  formOpen.value = true;
}

function closeForm() {
  if (store.saving) return;
  formOpen.value = false;
  store.clearMessages();
}

function sanitize(payload) {
  const next = { ...payload };
  delete next.application_id;
  [
    'application_environment_id',
    'integration_id',
    'owner_contact_id',
    'activated_at',
    'expires_at',
    'notes',
  ].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.activated_at) next.activated_at = new Date(next.activated_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  return next;
}

async function onSave(payload) {
  try {
    await store.updateAssignment(route.params.assignmentId, sanitize(payload));
    formOpen.value = false;
    await store.fetchTimeline(route.params.assignmentId);
  } catch {
    // Field errors stay in the modal via the store.
  }
}

async function confirmDelete() {
  await store.archiveAssignment(route.params.assignmentId);
  showDelete.value = false;
  await router.push({ name: 'customers.applications', params: { id: route.params.id } });
}

async function restore() {
  await store.restoreAssignment(route.params.assignmentId);
  await store.fetchAssignment(route.params.assignmentId);
  await store.fetchTimeline(route.params.assignmentId);
}
</script>
