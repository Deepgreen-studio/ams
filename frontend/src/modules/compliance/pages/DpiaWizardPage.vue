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
        v-if="assessmentId"
        :to="{ name: 'compliance.dpia.show', params: { id: assessmentId } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <EyeIcon class="h-4 w-4" />
        View assessment
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="pageLoading" class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
      <div class="mb-6 h-10 animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="space-y-4">
        <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>
    </div>

    <div v-else class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">
              {{ assessmentId ? 'Continue DPIA wizard' : 'Start DPIA wizard' }}
            </h2>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ stepMeta }}
            </p>
          </div>
          <p v-if="store.current?.assessment_number" class="text-xs font-medium text-slate-500">
            {{ store.current.assessment_number }}
          </p>
        </div>

        <ol class="mt-5 flex flex-wrap gap-2">
          <li v-for="step in steps" :key="step.id">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-[12px] px-3 py-2 text-sm font-medium transition"
              :class="stepButtonClass(step.id)"
              :disabled="!canGoToStep(step.id)"
              @click="goToStep(step.id)"
            >
              <span
                class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
                :class="stepIndexClass(step.id)"
              >
                <CheckIcon v-if="step.id < currentStep" class="h-3.5 w-3.5" />
                <span v-else>{{ step.id }}</span>
              </span>
              {{ step.label }}
            </button>
          </li>
        </ol>
      </div>

      <form class="space-y-5 px-6 py-6 sm:px-8" novalidate @submit.prevent="onSave">
        <p class="text-sm text-slate-600">{{ currentStepDef.description }}</p>

        <template v-if="currentStep === 1">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
              <SelectBox
                v-model="form.company_id"
                size="lg"
                placeholder="Select company"
                :options="companySelectOptions"
                :disabled="Boolean(assessmentId)"
                :error="Boolean(fieldError('company_id'))"
              />
              <p v-if="fieldError('company_id')" class="mt-1 text-xs text-rose-600">
                {{ fieldError('company_id') }}
              </p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Template</label>
              <SelectBox
                v-model="form.template_code"
                size="lg"
                :options="templateSelectOptions"
                :disabled="Boolean(assessmentId)"
                :error="Boolean(fieldError('template_code'))"
              />
              <p v-if="selectedTemplateHint" class="mt-1.5 text-xs text-slate-500">
                {{ selectedTemplateHint }}
              </p>
            </div>
            <div class="md:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
              <input
                v-model="form.title"
                type="text"
                class="input"
                maxlength="255"
                placeholder="e.g. Customer analytics platform DPIA"
                required
              />
              <p v-if="fieldError('title')" class="mt-1 text-xs text-rose-600">{{ fieldError('title') }}</p>
            </div>
            <div class="md:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
              <textarea
                v-model="form.description"
                rows="4"
                class="input"
                placeholder="Summarize the processing activity this assessment covers."
              />
            </div>
          </div>
        </template>

        <template v-else-if="currentStep === 2">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Processing purpose</label>
              <textarea
                v-model="form.processing_purpose"
                rows="4"
                class="input"
                placeholder="Why is personal data processed, and what outcome does it support?"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Data categories</label>
              <input
                v-model="categoriesInput"
                type="text"
                class="input"
                placeholder="email, name, location"
              />
              <p class="mt-1.5 text-xs text-slate-500">Comma-separated personal data types.</p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Data subjects</label>
              <input
                v-model="subjectsInput"
                type="text"
                class="input"
                placeholder="customers, employees"
              />
              <p class="mt-1.5 text-xs text-slate-500">Comma-separated groups of people.</p>
            </div>
            <div class="md:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Processing operations</label>
              <textarea
                v-model="form.processing_operations"
                rows="4"
                class="input"
                placeholder="Collection, storage, profiling, sharing, retention, deletion…"
              />
            </div>
          </div>
        </template>

        <template v-else-if="currentStep === 3">
          <div class="space-y-4">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Necessity & proportionality</label>
              <textarea
                v-model="form.necessity_proportionality"
                rows="5"
                class="input"
                placeholder="Explain why this processing is necessary and why a less intrusive option is not sufficient."
              />
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Consultation notes</label>
              <textarea
                v-model="form.consultation_notes"
                rows="4"
                class="input"
                placeholder="DPO, legal, security, or data subject consultation notes."
              />
            </div>
          </div>
        </template>

        <template v-else-if="currentStep === 4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Overall risk score</label>
              <input
                v-model.number="form.overall_risk_score"
                type="number"
                min="1"
                max="25"
                class="input"
                placeholder="1–25"
              />
              <p class="mt-1.5 text-xs text-slate-500">Likelihood × impact before mitigation.</p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Residual risk score</label>
              <input
                v-model.number="form.residual_risk_score"
                type="number"
                min="1"
                max="25"
                class="input"
                placeholder="1–25"
              />
              <p class="mt-1.5 text-xs text-slate-500">Score remaining after planned controls.</p>
            </div>
            <div class="sm:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Mitigation plan summary</label>
              <textarea
                v-model="form.mitigation_summary"
                rows="5"
                class="input"
                placeholder="Key technical and organisational measures that reduce residual risk."
              />
            </div>
          </div>
        </template>

        <template v-else>
          <div class="max-w-md">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Review due date</label>
            <input v-model="form.review_due_at" type="date" class="input" />
          </div>
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3 text-sm text-slate-600">
            Save this step, then submit the DPIA for approval from the assessment details page.
          </div>
        </template>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-5">
          <button
            type="button"
            class="inline-flex h-11 items-center gap-2 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentStep === 1 || store.saving"
            @click="currentStep -= 1"
          >
            <ChevronLeftIcon class="h-4 w-4" />
            Back
          </button>
          <div class="flex flex-wrap gap-2">
            <button
              type="submit"
              class="inline-flex h-11 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              {{ primaryLabel }}
            </button>
            <button
              v-if="currentStep < 5 && assessmentId"
              type="button"
              class="inline-flex h-11 items-center gap-2 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
              :disabled="store.saving"
              @click="saveAndNext"
            >
              Next
              <ChevronRightIcon class="h-4 w-4" />
            </button>
            <RouterLink
              v-if="currentStep === 5 && assessmentId"
              :to="{ name: 'compliance.dpia.show', params: { id: assessmentId } }"
              class="inline-flex h-11 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            >
              Open assessment
            </RouterLink>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import {
  CheckIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import { companyService } from '@/modules/companies/services/companyService';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const route = useRoute();
const router = useRouter();
const store = useDpiaStore();
const toast = useToast();

const companies = ref([]);
const assessmentId = ref(route.params.id || '');
const currentStep = ref(1);
const pageLoading = ref(Boolean(route.params.id));
const categoriesInput = ref('email, name');
const subjectsInput = ref('customers');

const steps = [
  { id: 1, label: 'Context', description: 'Company, template, and the processing activity this DPIA covers.' },
  { id: 2, label: 'Processing', description: 'What personal data is processed, who it relates to, and how it is used.' },
  { id: 3, label: 'Necessity', description: 'Why the processing is necessary, proportionate, and consulted.' },
  { id: 4, label: 'Risks', description: 'Score inherent and residual risk, then summarise mitigation.' },
  { id: 5, label: 'Review', description: 'Set the next review date before submitting for approval.' },
];

const form = reactive({
  company_id: '',
  template_code: 'standard',
  title: '',
  description: '',
  processing_purpose: '',
  processing_operations: '',
  necessity_proportionality: '',
  consultation_notes: '',
  overall_risk_score: null,
  residual_risk_score: null,
  mitigation_summary: '',
  review_due_at: '',
});

const currentStepDef = computed(() => steps.find((step) => step.id === currentStep.value) || steps[0]);

const stepMeta = computed(() => {
  const prefix = `Step ${currentStep.value} of ${steps.length} · ${currentStepDef.value.label}`;
  return assessmentId.value
    ? `${prefix}. Progress is saved when you continue.`
    : `${prefix}. Create the assessment to unlock later steps.`;
});

const primaryLabel = computed(() => {
  if (store.saving) {
    return assessmentId.value ? 'Saving…' : 'Creating…';
  }
  return assessmentId.value ? 'Save step' : 'Create & continue';
});

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const templateSelectOptions = computed(() =>
  (store.templates.length
    ? store.templates.map((tpl) => ({ value: tpl.code, label: tpl.label }))
    : [{ value: 'standard', label: 'Standard DPIA' }]),
);

const selectedTemplateHint = computed(() => {
  const tpl = store.templates.find((item) => item.code === form.template_code);
  return tpl?.defaults?.focus || '';
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

onMounted(async () => {
  store.successMessage = null;
  store.error = null;

  try {
    await store.fetchTemplates();
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }

  if (assessmentId.value) {
    try {
      const assessment = await store.fetchAssessment(assessmentId.value);
      hydrate(assessment);
    } catch {
      toast.error(store.error || 'Unable to load DPIA assessment');
    }
  }

  pageLoading.value = false;
});

function hydrate(assessment) {
  if (!assessment) {
    return;
  }

  Object.assign(form, {
    company_id: assessment.company?.uuid || '',
    template_code: assessment.template_code || 'standard',
    title: assessment.title || '',
    description: assessment.description || '',
    processing_purpose: assessment.processing_purpose || '',
    processing_operations: assessment.processing_operations || '',
    necessity_proportionality: assessment.necessity_proportionality || '',
    consultation_notes: assessment.consultation_notes || '',
    overall_risk_score: assessment.overall_risk_score,
    residual_risk_score: assessment.residual_risk_score,
    mitigation_summary: assessment.mitigation_summary || '',
    review_due_at: assessment.review_due_at || '',
  });
  categoriesInput.value = (assessment.data_categories || []).join(', ');
  subjectsInput.value = (assessment.data_subjects || []).join(', ');
  currentStep.value = assessment.wizard_step || 1;
}

function fieldError(key) {
  return store.fieldErrors?.[key]?.[0] || '';
}

function canGoToStep(id) {
  if (assessmentId.value) {
    return true;
  }
  return id === 1;
}

function goToStep(id) {
  if (!canGoToStep(id)) {
    return;
  }
  currentStep.value = id;
}

function stepButtonClass(id) {
  if (id === currentStep.value) {
    return 'bg-brand-50 text-brand-700';
  }
  if (id < currentStep.value) {
    return 'bg-zinc-50 text-slate-700 hover:bg-zinc-100';
  }
  return 'bg-white text-slate-500 ring-1 ring-zinc-200 hover:bg-zinc-50 disabled:hover:bg-white';
}

function stepIndexClass(id) {
  if (id === currentStep.value) {
    return 'bg-brand-600 text-white';
  }
  if (id < currentStep.value) {
    return 'bg-emerald-100 text-emerald-700';
  }
  return 'bg-zinc-100 text-slate-500';
}

function buildPayload() {
  return {
    ...form,
    wizard_step: currentStep.value,
    wizard_payload: { step: currentStep.value },
    data_categories: categoriesInput.value.split(',').map((s) => s.trim()).filter(Boolean),
    data_subjects: subjectsInput.value.split(',').map((s) => s.trim()).filter(Boolean),
    overall_risk_score: form.overall_risk_score || undefined,
    residual_risk_score: form.residual_risk_score || undefined,
    review_due_at: form.review_due_at || undefined,
  };
}

async function onSave() {
  const payload = buildPayload();

  try {
    if (!assessmentId.value) {
      const created = await store.createAssessment(payload);
      assessmentId.value = created.uuid;
      await router.replace({ name: 'compliance.dpia.wizard.edit', params: { id: created.uuid } });
      currentStep.value = Math.min(currentStep.value + 1, 5);
      return;
    }

    await store.saveWizard(assessmentId.value, payload);
  } catch {
    // Toast is shown from store.error.
  }
}

async function saveAndNext() {
  try {
    await store.saveWizard(assessmentId.value, buildPayload());
    currentStep.value = Math.min(currentStep.value + 1, 5);
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
