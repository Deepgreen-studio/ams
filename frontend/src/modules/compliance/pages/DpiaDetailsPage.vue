<template>
  <div>
    <!-- <PageHeader
      :title="store.current?.title || 'DPIA details'"
      :description="store.current?.assessment_number || 'Assessment workflow and linked risks'"
    >
      <template #actions>
        <RouterLink
          v-if="store.current"
          :to="{ name: 'compliance.dpia.wizard.edit', params: { id: store.current.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Continue wizard
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          v-if="store.current"
          :to="{ name: 'compliance.dpia.wizard.edit', params: { id: store.current.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Continue wizard
        </RouterLink>
    </Teleport>
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

    <template v-if="store.current">
      <div class="mb-4 flex flex-wrap gap-2">
        <DpiaStatusBadge :status="store.current.status" :label="store.current.status_label" />
        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
          {{ store.current.template_label }}
        </span>
        <BreachSeverityBadge
          v-if="store.current.overall_risk_level"
          :severity="store.current.overall_risk_level"
          :label="store.current.overall_risk_level_label"
        />
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Summary</h2>
          <p class="text-sm text-slate-600">{{ store.current.description || 'No description' }}</p>
          <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
            <div>
              <dt class="text-slate-500">Purpose</dt>
              <dd class="font-medium text-slate-900">{{ store.current.processing_purpose || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Review due</dt>
              <dd class="font-medium text-slate-900">{{ store.current.review_due_at || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Overall risk</dt>
              <dd class="font-medium text-slate-900">{{ store.current.overall_risk_score ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Residual risk</dt>
              <dd class="font-medium text-slate-900">{{ store.current.residual_risk_score ?? '—' }}</dd>
            </div>
          </dl>
          <p class="mt-4 text-sm text-slate-600">
            {{ store.current.mitigation_summary || 'No mitigation summary yet.' }}
          </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
          <h2 class="text-sm font-semibold text-slate-900">Approval workflow</h2>
          <button
            v-if="store.current.status === 'in_progress' || store.current.status === 'draft'"
            type="button"
            class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
            :disabled="store.saving"
            @click="store.submit(route.params.id)"
          >
            Submit for review
          </button>
          <template v-if="store.current.status === 'pending_review'">
            <textarea v-model="approvalNotes" rows="2" class="input" placeholder="Approval notes" />
            <button
              type="button"
              class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white"
              :disabled="store.saving"
              @click="onApprove"
            >
              Approve
            </button>
            <textarea v-model="rejectionNotes" rows="2" class="input" placeholder="Rejection notes" />
            <button
              type="button"
              class="w-full rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700"
              :disabled="store.saving"
              @click="onReject"
            >
              Reject
            </button>
          </template>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Linked risks</h2>
          <RouterLink
            :to="{ name: 'compliance.dpia.mitigation', query: { dpia: store.current.uuid } }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            Mitigation tracker
          </RouterLink>
        </div>
        <EmptyState
          v-if="!(store.current.risks || []).length"
          title="No linked risks"
          description="Register risks against this DPIA from the mitigation tracker."
        />
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="risk in store.current.risks" :key="risk.uuid" class="flex justify-between py-3">
            <div>
              <p class="font-medium text-slate-900">{{ risk.title }}</p>
              <p class="text-xs text-slate-500">{{ risk.risk_number }} · score {{ risk.risk_score }}</p>
            </div>
            <DpiaStatusBadge :status="risk.status" :label="risk.status_label" />
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import DpiaStatusBadge from '@/modules/compliance/components/DpiaStatusBadge.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const route = useRoute();
const store = useDpiaStore();
const approvalNotes = ref('');
const rejectionNotes = ref('');

onMounted(() => store.fetchAssessment(route.params.id));

async function onApprove() {
  await store.approve(route.params.id, { approval_notes: approvalNotes.value || undefined });
  await store.fetchAssessment(route.params.id);
}

async function onReject() {
  await store.reject(route.params.id, { rejection_notes: rejectionNotes.value });
  await store.fetchAssessment(route.params.id);
}
</script>

