<template>
  <div>
    <PageHeader
      :title="current?.subject_name || current?.subject_email || 'Consent details'"
      description="Consent audit details, metadata, and timeline."
    >
      <template #actions>
        <button
          v-if="current?.granted"
          type="button"
          class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
          :disabled="store.saving"
          @click="onWithdraw"
        >
          Withdraw consent
        </button>
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
          <ConsentStatusBadge :status="current.status" :label="current.status_label" />
          <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
            {{ current.consent_type?.name }}
          </span>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2 text-sm">
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Subject</dt>
            <dd class="mt-1 text-slate-900">
              {{ current.subject_name || '—' }} · {{ current.subject_email || '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Company</dt>
            <dd class="mt-1 text-slate-900">{{ current.company?.company_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Version</dt>
            <dd class="mt-1 text-slate-900">{{ current.consent_version }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Source</dt>
            <dd class="mt-1 text-slate-900">{{ current.source_label || current.source }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Consent date</dt>
            <dd class="mt-1 text-slate-900">{{ formatDate(current.consented_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Withdrawal date</dt>
            <dd class="mt-1 text-slate-900">{{ formatDate(current.withdrawn_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">IP address</dt>
            <dd class="mt-1 text-slate-900">{{ current.ip_address || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Device</dt>
            <dd class="mt-1 text-slate-900">{{ current.device || '—' }}</dd>
          </div>
        </dl>

        <div class="mt-6 border-t border-slate-100 pt-4">
          <h2 class="text-sm font-semibold text-slate-900">Notes</h2>
          <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">
            {{ current.notes || 'No notes.' }}
          </p>
        </div>
      </div>

      <ConsentTimeline :history="store.timeline" :loading="timelineLoading" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import ConsentStatusBadge from '@/modules/compliance/components/ConsentStatusBadge.vue';
import ConsentTimeline from '@/modules/compliance/components/ConsentTimeline.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';

const route = useRoute();
const store = useConsentStore();
const timelineLoading = ref(false);
const current = computed(() => store.current);

onMounted(async () => {
  await store.fetchConsent(route.params.id);
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

async function onWithdraw() {
  await store.withdrawConsent(route.params.id, { notes: 'Withdrawn from details view' });
  await store.fetchConsent(route.params.id);
  await store.fetchTimeline(route.params.id);
}
</script>
