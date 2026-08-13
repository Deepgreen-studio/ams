<template>
  <form class="space-y-5" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companySelectOptions"
          :disabled="loading"
          :error="Boolean(fieldError('company_id'))"
        />
        <p v-if="fieldError('company_id')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('company_id') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Consent type</label>
        <SelectBox
          v-model="form.consent_type_id"
          size="lg"
          placeholder="Select type"
          :options="typeSelectOptions"
          :disabled="loading || !types.length"
          :error="Boolean(fieldError('consent_type_id'))"
        />
        <p v-if="selectedTypeHint" class="mt-1.5 text-xs text-slate-500">{{ selectedTypeHint }}</p>
        <p v-else-if="fieldError('consent_type_id')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('consent_type_id') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject name</label>
        <input
          v-model="form.subject_name"
          type="text"
          class="input"
          maxlength="255"
          placeholder="Optional display name"
          :disabled="loading"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject email</label>
        <input
          v-model="form.subject_email"
          type="email"
          class="input"
          maxlength="255"
          placeholder="name@example.com"
          required
          :disabled="loading"
        />
        <p v-if="fieldError('subject_email')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('subject_email') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Source</label>
        <SelectBox
          v-model="form.source"
          size="lg"
          :options="sourceSelectOptions"
          :disabled="loading"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Decision</label>
        <SelectBox
          v-model="form.granted"
          size="lg"
          :options="decisionOptions"
          :disabled="loading"
        />
        <p class="mt-1.5 text-xs text-slate-500">
          {{ form.granted ? 'Records an opt-in for this channel.' : 'Records a withdrawal or denial.' }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Notes</label>
        <textarea
          v-model="form.notes"
          rows="5"
          class="input"
          maxlength="5000"
          placeholder="Optional context for the audit trail."
          :disabled="loading"
        />
      </div>
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-5">
      <button
        type="button"
        class="inline-flex h-11 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="inline-flex h-11 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading || !canSubmit"
      >
        {{ loading ? 'Saving…' : form.granted ? 'Record consent' : 'Record withdrawal' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { consentService } from '@/modules/compliance/services/consentService';
import { consentSourceOptions } from '@/modules/compliance/utils/consentOptions';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  fieldErrors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const types = ref([]);

const form = reactive({
  company_id: '',
  consent_type_id: '',
  subject_name: '',
  subject_email: '',
  source: 'admin',
  granted: true,
  notes: '',
});

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const typeSelectOptions = computed(() =>
  types.value.map((type) => ({
    value: type.uuid,
    label: type.name,
  })),
);

const sourceSelectOptions = computed(() => consentSourceOptions);

const decisionOptions = [
  { value: true, label: 'Grant' },
  { value: false, label: 'Withdraw / Deny' },
];

const selectedType = computed(
  () => types.value.find((type) => type.uuid === form.consent_type_id) || null,
);

const selectedTypeHint = computed(() => {
  const type = selectedType.value;
  if (!type) {
    return '';
  }

  return [
    type.channel_label || type.channel,
    type.current_version ? `Version ${type.current_version}` : null,
    type.is_required ? 'Required' : null,
  ]
    .filter(Boolean)
    .join(' · ');
});

const canSubmit = computed(
  () => Boolean(form.company_id && form.consent_type_id && form.subject_email),
);

onMounted(async () => {
  try {
    const [{ data: companyData }, { data: typeData }] = await Promise.all([
      companyService.list({ per_page: 100, status: 'active' }),
      consentService.types({ all: 1 }),
    ]);
    companies.value = companyData.data?.companies?.items ?? [];
    types.value = Array.isArray(typeData.data?.consent_types)
      ? typeData.data.consent_types
      : typeData.data?.consent_types?.items ?? [];
  } catch {
    companies.value = [];
    types.value = [];
  }
});

function fieldError(key) {
  const value = props.fieldErrors?.[key];
  return Array.isArray(value) ? value[0] : value || '';
}

function onSubmit() {
  if (!canSubmit.value || props.loading) {
    return;
  }

  emit('submit', {
    company_id: form.company_id,
    consent_type_id: form.consent_type_id,
    subject_name: form.subject_name || null,
    subject_email: form.subject_email,
    source: form.source,
    granted: form.granted,
    notes: form.notes || null,
  });
}
</script>
