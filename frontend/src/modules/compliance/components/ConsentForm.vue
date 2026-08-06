<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select v-model="form.company_id" class="input" required>
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Consent type</label>
        <select v-model="form.consent_type_id" class="input" required>
          <option value="" disabled>Select type</option>
          <option v-for="type in types" :key="type.uuid" :value="type.uuid">
            {{ type.name }}
          </option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Subject name</label>
        <input v-model="form.subject_name" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Subject email</label>
        <input v-model="form.subject_email" type="email" class="input" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Source</label>
        <select v-model="form.source" class="input">
          <option value="admin">Admin</option>
          <option value="preference_center">Preference Center</option>
          <option value="web">Web</option>
          <option value="mobile">Mobile</option>
          <option value="cookie_banner">Cookie Banner</option>
          <option value="api">API</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Decision</label>
        <select v-model="form.granted" class="input">
          <option :value="true">Grant</option>
          <option :value="false">Withdraw / Deny</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
        <textarea v-model="form.notes" rows="3" class="input" />
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : 'Record consent' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { consentService } from '@/modules/compliance/services/consentService';

defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
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

function onSubmit() {
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
