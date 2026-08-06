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
        <p v-if="errors.company_id" class="mt-1 text-xs text-rose-600">{{ errors.company_id[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Request type</label>
        <select v-model="form.request_type" class="input" required>
          <option v-for="option in typeOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.request_type" class="mt-1 text-xs text-rose-600">{{ errors.request_type[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Requester name</label>
        <input v-model="form.requester_name" type="text" class="input" required maxlength="255" />
        <p v-if="errors.requester_name" class="mt-1 text-xs text-rose-600">{{ errors.requester_name[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Requester email</label>
        <input v-model="form.requester_email" type="email" class="input" required />
        <p v-if="errors.requester_email" class="mt-1 text-xs text-rose-600">{{ errors.requester_email[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Requester phone</label>
        <input v-model="form.requester_phone" type="text" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Due date</label>
        <input v-model="form.due_date" type="date" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Assign officer</label>
        <select v-model="form.assigned_to" class="input">
          <option value="">Unassigned</option>
          <option v-for="user in users" :key="user.uuid" :value="user.uuid">
            {{ user.full_name }} ({{ user.email }})
          </option>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="5" class="input" />
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
import { onMounted, reactive, ref } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { userService } from '@/modules/users/services/userService';

defineProps({
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Create request' },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const users = ref([]);

const typeOptions = [
  { value: 'access_request', label: 'Access Request' },
  { value: 'data_export', label: 'Data Export' },
  { value: 'data_correction', label: 'Data Correction' },
  { value: 'data_deletion', label: 'Data Deletion' },
  { value: 'restrict_processing', label: 'Right to Restrict Processing' },
  { value: 'right_to_object', label: 'Right to Object' },
  { value: 'consent_withdrawal', label: 'Consent Withdrawal' },
  { value: 'data_portability', label: 'Data Portability' },
];

const form = reactive({
  company_id: '',
  request_type: 'access_request',
  requester_name: '',
  requester_email: '',
  requester_phone: '',
  assigned_to: '',
  due_date: '',
  description: '',
});

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
  emit('submit', {
    company_id: form.company_id,
    request_type: form.request_type,
    requester_name: form.requester_name,
    requester_email: form.requester_email,
    requester_phone: form.requester_phone || null,
    assigned_to: form.assigned_to || null,
    due_date: form.due_date || null,
    description: form.description || null,
  });
}
</script>
