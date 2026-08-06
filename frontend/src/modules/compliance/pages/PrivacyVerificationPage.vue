<template>
  <div>
    <PageHeader
      title="Identity verification"
      description="Verify the requester before progressing the approval workflow."
    >
      <template #actions>
        <RouterLink
          v-if="current"
          :to="{ name: 'compliance.privacy.show', params: { id: current.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to request
        </RouterLink>
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

    <div v-else-if="current" class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-sm font-semibold text-slate-900">Requester profile</h2>
        <dl class="mt-4 space-y-3 text-sm">
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Name</dt>
            <dd class="mt-1 text-slate-900">{{ current.requester_name }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Email</dt>
            <dd class="mt-1 text-slate-900">{{ current.requester_email }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Phone</dt>
            <dd class="mt-1 text-slate-900">{{ current.requester_phone || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Request</dt>
            <dd class="mt-1 text-slate-900">
              {{ current.request_number }} · {{ current.request_type_label }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
            <dd class="mt-1">
              <PrivacyStatusBadge :status="current.status" :label="current.status_label" />
            </dd>
          </div>
        </dl>
      </div>

      <PrivacyVerificationPanel
        :request="current"
        :loading="store.saving"
        @verify="onVerify"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PrivacyStatusBadge from '@/modules/compliance/components/PrivacyStatusBadge.vue';
import PrivacyVerificationPanel from '@/modules/compliance/components/PrivacyVerificationPanel.vue';
import { usePrivacyRequestsStore } from '@/modules/compliance/stores/privacyRequests';

const route = useRoute();
const store = usePrivacyRequestsStore();
const current = computed(() => store.current);

onMounted(() => {
  store.fetchRequest(route.params.id);
});

async function onVerify(payload) {
  await store.verifyIdentity(route.params.id, payload);
  await store.fetchRequest(route.params.id);
}
</script>
