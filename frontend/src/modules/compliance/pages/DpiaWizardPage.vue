<template>
  <div>
    <!-- <PageHeader
      title="DPIA wizard"
      description="Guided data protection impact assessment using enterprise templates."
    /> -->
    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <div class="mb-6 flex flex-wrap gap-2">
        <button
          v-for="step in steps"
          :key="step.id"
          type="button"
          class="rounded-lg px-3 py-1.5 text-xs font-medium"
          :class="step.id === currentStep ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600'"
          @click="currentStep = step.id"
        >
          {{ step.id }}. {{ step.label }}
        </button>
      </div>

      <form class="space-y-4" @submit.prevent="onSave">
        <template v-if="currentStep === 1">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
            <select v-model="form.company_id" class="input" required :disabled="!!assessmentId">
              <option value="" disabled>Select company</option>
              <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
                {{ company.company_name }}
              </option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Template</label>
            <select v-model="form.template_code" class="input" :disabled="!!assessmentId">
              <option v-for="tpl in store.templates" :key="tpl.code" :value="tpl.code">
                {{ tpl.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
            <input v-model="form.title" type="text" class="input" required />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="3" class="input" />
          </div>
        </template>

        <template v-else-if="currentStep === 2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Processing purpose</label>
            <textarea v-model="form.processing_purpose" rows="3" class="input" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Data categories</label>
            <input v-model="categoriesInput" type="text" class="input" placeholder="email, name, location" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Data subjects</label>
            <input v-model="subjectsInput" type="text" class="input" placeholder="customers, employees" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Processing operations</label>
            <textarea v-model="form.processing_operations" rows="3" class="input" />
          </div>
        </template>

        <template v-else-if="currentStep === 3">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Necessity & proportionality</label>
            <textarea v-model="form.necessity_proportionality" rows="4" class="input" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Consultation notes</label>
            <textarea v-model="form.consultation_notes" rows="3" class="input" />
          </div>
        </template>

        <template v-else-if="currentStep === 4">
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Overall risk score (1-25)</label>
              <input v-model.number="form.overall_risk_score" type="number" min="1" max="25" class="input" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Residual risk score</label>
              <input v-model.number="form.residual_risk_score" type="number" min="1" max="25" class="input" />
            </div>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Mitigation plan summary</label>
            <textarea v-model="form.mitigation_summary" rows="4" class="input" />
          </div>
        </template>

        <template v-else>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Review due date</label>
            <input v-model="form.review_due_at" type="date" class="input" />
          </div>
          <p class="text-sm text-slate-600">
            Save this step, then submit the DPIA for approval from the assessment details page.
          </p>
        </template>

        <div class="flex justify-between gap-3 pt-2">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700"
            :disabled="currentStep === 1"
            @click="currentStep -= 1"
          >
            Back
          </button>
          <div class="flex gap-2">
            <button
              type="submit"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
              :disabled="store.saving"
            >
              {{ store.saving ? 'Saving...' : assessmentId ? 'Save step' : 'Create & continue' }}
            </button>
            <button
              v-if="currentStep < 5"
              type="button"
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700"
              @click="currentStep += 1"
            >
              Next
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { companyService } from '@/modules/companies/services/companyService';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const route = useRoute();
const router = useRouter();
const store = useDpiaStore();

const companies = ref([]);
const assessmentId = ref(route.params.id || '');
const currentStep = ref(1);
const categoriesInput = ref('email, name');
const subjectsInput = ref('customers');
const steps = [
  { id: 1, label: 'Context' },
  { id: 2, label: 'Processing' },
  { id: 3, label: 'Necessity' },
  { id: 4, label: 'Risks' },
  { id: 5, label: 'Review' },
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

onMounted(async () => {
  await store.fetchTemplates();
  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }

  if (assessmentId.value) {
    const assessment = await store.fetchAssessment(assessmentId.value);
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
});

async function onSave() {
  const payload = {
    ...form,
    wizard_step: currentStep.value,
    wizard_payload: { step: currentStep.value },
    data_categories: categoriesInput.value.split(',').map((s) => s.trim()).filter(Boolean),
    data_subjects: subjectsInput.value.split(',').map((s) => s.trim()).filter(Boolean),
    overall_risk_score: form.overall_risk_score || undefined,
    residual_risk_score: form.residual_risk_score || undefined,
    review_due_at: form.review_due_at || undefined,
  };

  if (!assessmentId.value) {
    const created = await store.createAssessment(payload);
    assessmentId.value = created.uuid;
    await router.replace({ name: 'compliance.dpia.wizard.edit', params: { id: created.uuid } });
    currentStep.value = Math.min(currentStep.value + 1, 5);
    return;
  }

  await store.saveWizard(assessmentId.value, payload);
}
</script>

