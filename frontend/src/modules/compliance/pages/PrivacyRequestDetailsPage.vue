<template>
  <div>
    <PageHeader
      :title="current?.requester_name || 'Privacy request'"
      description="Verification, approval, fulfilment, and timeline for this GDPR request."
    >
      <template #actions>
        <template v-if="current">
          <RouterLink
            :to="{ name: 'compliance.privacy.verify', params: { id: current.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Verification
          </RouterLink>
          <button
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
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
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
          <PrivacyStatusBadge :status="current.status" :label="current.status_label" />
          <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
            {{ current.request_type_label || current.request_type }}
          </span>
          <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
            {{ current.identity_verification_status_label }}
          </span>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Request number</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ current.request_number }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Company</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ current.company?.company_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Requester</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ current.requester_name }} · {{ current.requester_email }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Assignee</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ current.assignee?.full_name || 'Unassigned' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Due date</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ current.due_date || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Decision</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ current.decision_label || current.decision || '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Completed at</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ formatDate(current.completed_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Export / deletion</dt>
            <dd class="mt-1 text-sm text-slate-900">
              Export: {{ current.has_export ? 'Generated' : 'Not generated' }} · Deletion:
              {{ current.deletion_confirmed_at ? 'Confirmed' : 'Not confirmed' }}
            </dd>
          </div>
        </dl>

        <div class="mt-6 border-t border-slate-100 pt-4">
          <h2 class="text-sm font-semibold text-slate-900">Description</h2>
          <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">
            {{ current.description || 'No description provided.' }}
          </p>
        </div>
      </div>

      <PrivacyApprovalPanel
        :request="current"
        :loading="store.saving"
        @approve="onApprove"
        @reject="onReject"
        @export="onExport"
        @download="onDownload"
        @delete-data="onDeleteData"
        @complete="onComplete"
      />

      <PrivacySupportConversationPanel
        :privacy-request-id="current.uuid"
        :ticket="current.support_ticket"
      />

      <PrivacyTimeline :history="store.timeline" :loading="timelineLoading" />
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete privacy request"
      :message="`Soft delete ${current?.request_number || 'this request'}?`"
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
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyApprovalPanel from '@/modules/compliance/components/PrivacyApprovalPanel.vue';
import PrivacyStatusBadge from '@/modules/compliance/components/PrivacyStatusBadge.vue';
import PrivacySupportConversationPanel from '@/modules/compliance/components/PrivacySupportConversationPanel.vue';
import PrivacyTimeline from '@/modules/compliance/components/PrivacyTimeline.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';

const route = useRoute();
const router = useRouter();
const store = usePrivacyRequestsStore();
const showDelete = ref(false);
const timelineLoading = ref(false);

const current = computed(() => store.current);

onMounted(async () => {
  await store.fetchRequest(route.params.id);
  timelineLoading.value = true;
  try {
    await store.fetchTimeline(route.params.id);
  } finally {
    timelineLoading.value = false;
  }
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function refresh() {
  await store.fetchRequest(route.params.id);
  await store.fetchTimeline(route.params.id);
}

async function onApprove(payload) {
  await store.approve(route.params.id, payload);
  await refresh();
}

async function onReject(payload) {
  await store.reject(route.params.id, payload);
  await refresh();
}

async function onExport() {
  await store.generateExport(route.params.id);
  await refresh();
}

async function onDownload() {
  await store.downloadExport(route.params.id);
}

async function onDeleteData(payload) {
  await store.confirmDeletion(route.params.id, payload);
  await refresh();
}

async function onComplete(payload) {
  await store.complete(route.params.id, payload);
  await refresh();
}

async function confirmDelete() {
  await store.deleteRequest(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'compliance.privacy.index' });
}
</script>
