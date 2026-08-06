<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select v-model="form.company_id" class="input" required :disabled="Boolean(initial?.uuid)">
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Policy type</label>
        <select v-model="form.policy_type" class="input" required>
          <option v-for="type in policyTypes" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
        <input v-model="form.title" type="text" class="input" required maxlength="255" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="2" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Body</label>
        <textarea v-model="form.body" rows="10" class="input font-mono text-sm" required />
      </div>
      <div v-if="initial?.uuid" class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Change summary</label>
        <input
          v-model="form.change_summary"
          type="text"
          class="input"
          placeholder="Why this revision exists (stored on the new version)"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Effective at</label>
        <input v-model="form.effective_at" type="datetime-local" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Review due</label>
        <input v-model="form.review_due_at" type="date" class="input" />
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
        {{ loading ? 'Saving...' : initial?.uuid ? 'Save as new version' : 'Create policy' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';

const props = defineProps({
  initial: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const policyTypes = [
  { value: 'privacy_policy', label: 'Privacy Policy' },
  { value: 'terms', label: 'Terms & Conditions' },
  { value: 'cookie_policy', label: 'Cookie Policy' },
  { value: 'security_policy', label: 'Security Policy' },
  { value: 'internal_policy', label: 'Internal Policy' },
  { value: 'employee_handbook', label: 'Employee Handbook' },
  { value: 'compliance_document', label: 'Compliance Document' },
];

const companies = ref([]);
const form = reactive({
  company_id: '',
  title: '',
  policy_type: 'privacy_policy',
  description: '',
  body: '',
  change_summary: '',
  effective_at: '',
  review_due_at: '',
});

function hydrate() {
  const item = props.initial || {};
  form.company_id = item.company?.uuid || item.company_id || '';
  form.title = item.title || '';
  form.policy_type = item.policy_type || 'privacy_policy';
  form.description = item.description || '';
  form.body = item.body || '';
  form.change_summary = '';
  form.effective_at = item.effective_at ? String(item.effective_at).slice(0, 16) : '';
  form.review_due_at = item.review_due_at || '';
}

watch(() => props.initial, hydrate, { immediate: true });

onMounted(async () => {
  try {
    const { data } = await companyService.list({ per_page: 100 });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function onSubmit() {
  const payload = {
    title: form.title,
    policy_type: form.policy_type,
    description: form.description || null,
    body: form.body,
    review_due_at: form.review_due_at || null,
    effective_at: form.effective_at || null,
  };
  if (!props.initial?.uuid) {
    payload.company_id = form.company_id;
  } else if (form.change_summary) {
    payload.change_summary = form.change_summary;
  }
  emit('submit', payload);
}
</script>
