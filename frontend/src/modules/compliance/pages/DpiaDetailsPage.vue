<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.dpia.history' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ClockIcon class="h-4 w-4" />
        History
      </RouterLink>
      <RouterLink
        v-if="assessment && canContinue"
        :to="{ name: 'compliance.dpia.wizard.edit', params: { id: assessment.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <PencilSquareIcon class="h-4 w-4" />
        Continue wizard
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !assessment" class="grid gap-4 lg:grid-cols-3">
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100 lg:col-span-2" />
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="!assessment"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load this DPIA</p>
      <p class="mt-1 text-xs text-slate-500">It may have been removed, or the request failed.</p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="reload"
        >
          Retry
        </button>
        <RouterLink
          :to="{ name: 'compliance.dpia.history' }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back to history
        </RouterLink>
      </div>
    </div>

    <div v-else class="space-y-4">
      <section class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-center gap-2">
          <DpiaStatusBadge :status="assessment.status" :label="assessment.status_label" />
          <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-600">
            {{ assessment.template_label || assessment.template_code || 'DPIA' }}
          </span>
          <BreachSeverityBadge
            v-if="assessment.overall_risk_level"
            :severity="assessment.overall_risk_level"
            :label="assessment.overall_risk_level_label"
          />
        </div>
        <h1 class="mt-3 text-lg font-semibold text-slate-900">{{ assessment.title }}</h1>
        <p class="mt-1 text-xs text-slate-500">
          {{ assessment.assessment_number }}
          <span v-if="assessment.company?.company_name"> · {{ assessment.company.company_name }}</span>
          <span v-if="assessment.assignee?.full_name"> · {{ assessment.assignee.full_name }}</span>
        </p>
      </section>

      <div class="grid items-start gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Summary</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">
              {{ assessment.description || 'No description provided.' }}
            </p>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Purpose</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ assessment.processing_purpose || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Review due</dt>
                <dd
                  class="mt-1 text-sm"
                  :class="isReviewOverdue ? 'font-medium text-rose-600' : 'text-slate-900'"
                >
                  {{ reviewDueLabel }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Overall risk</dt>
                <dd class="mt-1 text-sm text-slate-900">
                  {{ assessment.overall_risk_score ?? '—' }}
                  <span v-if="assessment.overall_risk_level_label" class="text-slate-500">
                    · {{ assessment.overall_risk_level_label }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Residual risk</dt>
                <dd class="mt-1 text-sm text-slate-900">
                  {{ assessment.residual_risk_score ?? '—' }}
                  <span v-if="assessment.residual_risk_level_label" class="text-slate-500">
                    · {{ assessment.residual_risk_level_label }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Wizard step</dt>
                <dd class="mt-1 text-sm text-slate-900">
                  {{ assessment.wizard_step ? `Step ${assessment.wizard_step} of 5` : '—' }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Assignee</dt>
                <dd class="mt-1 text-sm text-slate-900">
                  {{ assessment.assignee?.full_name || 'Unassigned' }}
                </dd>
              </div>
            </dl>

            <div class="mt-5 space-y-4 border-t border-zinc-100 pt-5">
              <div>
                <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Data categories</h3>
                <p class="mt-1 text-sm text-slate-900">{{ listLabel(assessment.data_categories) }}</p>
              </div>
              <div>
                <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Data subjects</h3>
                <p class="mt-1 text-sm text-slate-900">{{ listLabel(assessment.data_subjects) }}</p>
              </div>
              <div>
                <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Processing operations</h3>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">
                  {{ assessment.processing_operations || '—' }}
                </p>
              </div>
              <div>
                <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Necessity & proportionality</h3>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">
                  {{ assessment.necessity_proportionality || '—' }}
                </p>
              </div>
              <div>
                <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Mitigation summary</h3>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">
                  {{ assessment.mitigation_summary || 'No mitigation summary yet.' }}
                </p>
              </div>
            </div>
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div>
                <h2 class="text-base font-semibold text-slate-900">Linked risks</h2>
                <p class="mt-0.5 text-xs text-slate-500">Risks registered against this assessment</p>
              </div>
              <RouterLink
                :to="{ name: 'compliance.dpia.mitigation', query: { dpia: assessment.uuid } }"
                class="text-xs font-medium text-brand-700 hover:underline"
              >
                Mitigation tracker
              </RouterLink>
            </div>
            <div v-if="!linkedRisks.length" class="py-10 text-center">
              <p class="text-sm font-medium text-slate-900">No linked risks</p>
              <p class="mt-1 text-xs text-slate-500">
                Register risks against this DPIA from the mitigation tracker.
              </p>
            </div>
            <ul v-else class="divide-y divide-zinc-100">
              <li
                v-for="risk in linkedRisks"
                :key="risk.uuid"
                class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
              >
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-slate-900">{{ risk.title }}</p>
                  <p class="mt-1 text-xs text-slate-500">
                    {{ [risk.risk_number, risk.risk_score != null ? `Score ${risk.risk_score}` : null, risk.owner?.full_name].filter(Boolean).join(' · ') }}
                  </p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1.5 sm:flex-row sm:items-center">
                  <BreachSeverityBadge
                    v-if="risk.risk_level"
                    :severity="risk.risk_level"
                    :label="risk.risk_level_label"
                  />
                  <DpiaStatusBadge :status="risk.status" :label="risk.status_label" />
                </div>
              </li>
            </ul>
          </section>
        </div>

        <aside class="space-y-4">
          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Approval workflow</h2>
            <p class="mt-1 text-xs text-slate-500">{{ workflowHint }}</p>

            <dl class="mt-4 space-y-3 text-sm">
              <div class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5">
                <dt class="text-slate-500">Status</dt>
                <dd class="font-medium text-slate-900">{{ assessment.status_label || assessment.status }}</dd>
              </div>
              <div class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5">
                <dt class="text-slate-500">Submitted</dt>
                <dd class="font-medium text-slate-900">{{ formatDate(assessment.submitted_at) }}</dd>
              </div>
              <div class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5">
                <dt class="text-slate-500">Approved</dt>
                <dd class="font-medium text-slate-900">{{ formatDate(assessment.approved_at) }}</dd>
              </div>
            </dl>

            <div v-if="canSubmit && can('compliance.update')" class="mt-4">
              <button
                type="button"
                class="inline-flex h-11 w-full items-center justify-center rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                :disabled="store.saving"
                @click="onSubmit"
              >
                {{ store.saving ? 'Submitting…' : 'Submit for review' }}
              </button>
            </div>

            <div v-else-if="canDecide && can('compliance.update')" class="mt-4 space-y-3">
              <textarea
                v-model="approvalNotes"
                rows="2"
                class="input"
                placeholder="Approval notes (optional)"
              />
              <button
                type="button"
                class="inline-flex h-11 w-full items-center justify-center rounded-[12px] bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
                :disabled="store.saving"
                @click="onApprove"
              >
                {{ store.saving ? 'Saving…' : 'Approve' }}
              </button>
              <textarea
                v-model="rejectionNotes"
                rows="2"
                class="input"
                placeholder="Rejection notes"
              />
              <button
                type="button"
                class="inline-flex h-11 w-full items-center justify-center rounded-[12px] border border-rose-200 px-4 text-sm font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-60"
                :disabled="store.saving"
                @click="onReject"
              >
                Reject
              </button>
            </div>

            <p
              v-if="assessment.approval_notes || assessment.rejection_notes"
              class="mt-4 whitespace-pre-wrap rounded-[12px] bg-zinc-50 px-3.5 py-3 text-sm text-slate-600"
            >
              {{ assessment.approval_notes || assessment.rejection_notes }}
            </p>
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">People</h2>
            <dl class="mt-4 space-y-3 text-sm">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created by</dt>
                <dd class="mt-1 text-slate-900">{{ assessment.creator?.full_name || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Submitter</dt>
                <dd class="mt-1 text-slate-900">{{ assessment.submitter?.full_name || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Approver</dt>
                <dd class="mt-1 text-slate-900">{{ assessment.approver?.full_name || '—' }}</dd>
              </div>
            </dl>
          </section>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ClockIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import DpiaStatusBadge from '@/modules/compliance/components/DpiaStatusBadge.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const route = useRoute();
const store = useDpiaStore();
const toast = useToast();
const { can } = usePermissions();
const approvalNotes = ref('');
const rejectionNotes = ref('');

const assessment = computed(() => store.current);
const linkedRisks = computed(() => assessment.value?.risks || []);
const canContinue = computed(() =>
  ['draft', 'in_progress', 'rejected'].includes(assessment.value?.status),
);
const canSubmit = computed(() => ['draft', 'in_progress'].includes(assessment.value?.status));
const canDecide = computed(() => assessment.value?.status === 'pending_review');

const isReviewOverdue = computed(() => {
  const due = assessment.value?.review_due_at;
  if (!due || assessment.value?.status === 'archived') {
    return false;
  }
  return String(due) < new Date().toISOString().slice(0, 10);
});

const reviewDueLabel = computed(() => {
  const due = assessment.value?.review_due_at;
  if (!due) {
    return '—';
  }
  return isReviewOverdue.value ? `Overdue ${due}` : due;
});

const workflowHint = computed(() => {
  switch (assessment.value?.status) {
    case 'draft':
    case 'in_progress':
      return 'Finish the wizard, then submit this assessment for approval.';
    case 'pending_review':
      return 'A reviewer can approve or reject this DPIA.';
    case 'approved':
      return 'This assessment is approved. Keep residual risk under review.';
    case 'rejected':
      return 'Update the wizard and resubmit after addressing the rejection notes.';
    case 'archived':
      return 'This assessment has been archived.';
    default:
      return 'Track submission and approval for this DPIA.';
  }
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

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  reload();
});

function listLabel(value) {
  if (Array.isArray(value) && value.length) {
    return value.join(', ');
  }
  return '—';
}

function formatDate(value) {
  if (!value) {
    return '—';
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '—';
  }
  return date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

async function reload() {
  try {
    await store.fetchAssessment(route.params.id);
  } catch {
    // Toast is shown from store.error.
  }
}

async function onSubmit() {
  try {
    await store.submit(route.params.id);
    await store.fetchAssessment(route.params.id);
  } catch {
    // Toast is shown from store.error.
  }
}

async function onApprove() {
  try {
    await store.approve(route.params.id, { approval_notes: approvalNotes.value || undefined });
    approvalNotes.value = '';
    await store.fetchAssessment(route.params.id);
  } catch {
    // Toast is shown from store.error.
  }
}

async function onReject() {
  if (!rejectionNotes.value.trim()) {
    toast.error('Add rejection notes before rejecting this DPIA.');
    return;
  }

  try {
    await store.reject(route.params.id, { rejection_notes: rejectionNotes.value });
    rejectionNotes.value = '';
    await store.fetchAssessment(route.params.id);
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
