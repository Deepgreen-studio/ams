<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select v-model="form.company_id" class="input" required :disabled="Boolean(initial.uuid)">
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
        <p v-if="errors.company_id" class="mt-1 text-xs text-rose-600">{{ errors.company_id[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Case type</label>
        <select v-model="form.case_type" class="input" required>
          <option v-for="option in typeOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.case_type" class="mt-1 text-xs text-rose-600">{{ errors.case_type[0] }}</p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
        <input v-model="form.title" type="text" class="input" required maxlength="255" />
        <p v-if="errors.title" class="mt-1 text-xs text-rose-600">{{ errors.title[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Priority</label>
        <select v-model="form.priority" class="input" required>
          <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.priority" class="mt-1 text-xs text-rose-600">{{ errors.priority[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input" required>
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.status" class="mt-1 text-xs text-rose-600">{{ errors.status[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Assign to</label>
        <select v-model="form.assigned_to" class="input">
          <option value="">Unassigned</option>
          <option v-for="user in users" :key="user.uuid" :value="user.uuid">
            {{ user.full_name }} ({{ user.email }})
          </option>
        </select>
        <p v-if="errors.assigned_to" class="mt-1 text-xs text-rose-600">{{ errors.assigned_to[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Due date</label>
        <input v-model="form.due_date" type="date" class="input" />
        <p v-if="errors.due_date" class="mt-1 text-xs text-rose-600">{{ errors.due_date[0] }}</p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="6" class="input" />
        <p v-if="errors.description" class="mt-1 text-xs text-rose-600">{{ errors.description[0] }}</p>
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
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { userService } from '@/modules/users/services/userService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Save case' },
  hideCompany: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const users = ref([]);

const typeOptions = [
  { value: 'gdpr', label: 'GDPR' },
  { value: 'uk_gdpr', label: 'UK GDPR' },
  { value: 'privacy_request', label: 'Privacy Request' },
  { value: 'compliance_case', label: 'Compliance Case' },
  { value: 'risk_register', label: 'Risk Register' },
  { value: 'audit_compliance', label: 'Audit Compliance' },
  { value: 'iso_27001', label: 'ISO 27001' },
  { value: 'soc2', label: 'SOC 2' },
  { value: 'other', label: 'Other' },
];

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
];

const statusOptions = [
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'under_review', label: 'Under Review' },
  { value: 'pending', label: 'Pending' },
  { value: 'completed', label: 'Completed' },
  { value: 'closed', label: 'Closed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const form = reactive({
  company_id: '',
  title: '',
  description: '',
  case_type: 'compliance_case',
  priority: 'medium',
  status: 'open',
  assigned_to: '',
  due_date: '',
});

function syncFromInitial() {
  form.company_id = props.initial.company?.uuid || props.initial.company_id || '';
  form.title = props.initial.title || '';
  form.description = props.initial.description || '';
  form.case_type = props.initial.case_type || 'compliance_case';
  form.priority = props.initial.priority || 'medium';
  form.status = props.initial.status || 'open';
  form.assigned_to = props.initial.assignee?.uuid || '';
  form.due_date = props.initial.due_date || '';
}

watch(() => props.initial, syncFromInitial, { immediate: true, deep: true });

onMounted(async () => {
  try {
    const [{ data: companyData }, { data: userData }] = await Promise.all([
      companyService.list({ per_page: 100, status: 'active' }),
      userService.list({ per_page: 100 }),
    ]);
    companies.value = companyData.data?.companies?.items ?? [];
    users.value = userData.data?.users?.items ?? userData.data?.users ?? [];
  } catch {
    companies.value = [];
    users.value = [];
  }
});

function onSubmit() {
  const payload = {
    title: form.title,
    description: form.description || null,
    case_type: form.case_type,
    priority: form.priority,
    status: form.status,
    assigned_to: form.assigned_to || null,
    due_date: form.due_date || null,
  };

  if (!props.initial.uuid) {
    payload.company_id = form.company_id;
  }

  emit('submit', payload);
}
</script>
