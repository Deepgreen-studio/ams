<template>
  <form class="space-y-4" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select
          v-model="form.company_id"
          class="input"
          :class="fieldClass('company_id')"
          :disabled="Boolean(initial.uuid)"
        >
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
        <p v-if="displayErrors.company_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Customer</label>
        <select v-model="form.customer_id" class="input" :disabled="!form.company_id">
          <option value="">Optional</option>
          <option v-for="customer in filteredCustomers" :key="customer.uuid" :value="customer.uuid">
            {{ customer.display_name || customer.email }}
          </option>
        </select>
        <p v-if="displayErrors.customer_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.customer_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Application</label>
        <select v-model="form.application_id" class="input" :disabled="!form.company_id">
          <option value="">Optional</option>
          <option
            v-for="application in filteredApplications"
            :key="application.uuid"
            :value="application.uuid"
          >
            {{ application.name }}
          </option>
        </select>
        <p v-if="displayErrors.application_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.application_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
        <select
          v-model="form.category"
          class="input"
          :class="fieldClass('category')"
        >
          <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="displayErrors.category" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.category[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Priority</label>
        <select
          v-model="form.priority"
          class="input"
          :class="fieldClass('priority')"
        >
          <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="displayErrors.priority" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.priority[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Source</label>
        <select
          v-model="form.source"
          class="input"
          :class="fieldClass('source')"
        >
          <option v-for="option in sourceOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="displayErrors.source" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.source[0] }}
        </p>
      </div>

      <div v-if="initial.uuid">
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select
          v-model="form.status"
          class="input"
          :class="fieldClass('status')"
        >
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p v-if="displayErrors.status" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.status[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Assign to</label>
        <select v-model="form.assigned_to" class="input">
          <option value="">Unassigned</option>
          <option v-for="user in users" :key="user.uuid" :value="user.uuid">
            {{ user.full_name }} ({{ user.email }})
          </option>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Subject</label>
        <input
          v-model="form.subject"
          type="text"
          class="input"
          maxlength="255"
          :class="fieldClass('subject')"
        />
        <p v-if="displayErrors.subject" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.subject[0] }}
        </p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="6"
          class="input"
          :class="fieldClass('description')"
        />
        <p v-if="displayErrors.description" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.description[0] }}
        </p>
      </div>

      <div class="md:col-span-2">
        <label class="inline-flex items-start gap-2 text-sm text-slate-700">
          <input v-model="form.involves_personal_data" type="checkbox" class="mt-1 rounded border-slate-300" />
          <span>
            Involves personal data (auto-route to Compliance if Support cannot handle)
            <span class="block text-xs text-slate-500">
              Health/privacy requests escalate to a linked Compliance privacy request. Operational
              account disable stays in Support.
            </span>
          </span>
        </label>
        <p v-if="displayErrors.involves_personal_data" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.involves_personal_data[0] }}
        </p>
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        :disabled="loading"
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';
import { applicationService } from '@/modules/applications/services/applicationService';
import { companyService } from '@/modules/companies/services/companyService';
import { customerService } from '@/modules/customers/services/customerService';
import {
  categoryOptions,
  priorityOptions,
  sourceOptions,
  statusOptions,
} from '@/modules/support/utils/ticketOptions';
import { userService } from '@/modules/users/services/userService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Save ticket' },
});

const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();

const companies = ref([]);
const customers = ref([]);
const applications = ref([]);
const users = ref([]);
const localErrors = ref({});

const form = reactive({
  company_id: '',
  customer_id: '',
  application_id: '',
  subject: '',
  description: '',
  priority: 'medium',
  category: 'customer_support',
  status: 'open',
  source: 'portal',
  assigned_to: '',
  involves_personal_data: false,
});

const displayErrors = computed(() => ({
  ...localErrors.value,
  ...props.errors,
}));

const filteredCustomers = computed(() => {
  if (!form.company_id) {
    return customers.value;
  }

  return customers.value.filter((item) => item.company?.uuid === form.company_id || !item.company);
});

const filteredApplications = computed(() => {
  if (!form.company_id) {
    return applications.value;
  }

  return applications.value.filter((item) => item.company?.uuid === form.company_id || !item.company);
});

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, {
      company_id: value.company?.uuid || value.company_id || '',
      customer_id: value.customer?.uuid || value.customer_id || '',
      application_id: value.application?.uuid || value.application_id || '',
      subject: value.subject || '',
      description: value.description || '',
      priority: value.priority || 'medium',
      category: value.category || 'customer_support',
      status: value.status || 'open',
      source: value.source || 'portal',
      assigned_to: value.assignee?.uuid || value.assigned_to || '',
      involves_personal_data: Boolean(value.involves_personal_data),
    });
    localErrors.value = {};
  },
  { immediate: true, deep: true }
);

watch(
  () => props.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  }
);

watch(
  () => props.errors,
  () => {
    localErrors.value = {};
  },
  { deep: true }
);

watch(
  () => form.company_id,
  async (companyId, previous) => {
    if (previous && companyId !== previous) {
      form.customer_id = '';
      form.application_id = '';
    }

    if (!companyId) {
      return;
    }

    await Promise.all([loadCustomers(companyId), loadApplications(companyId)]);
  }
);

onMounted(async () => {
  await Promise.all([loadCompanies(), loadUsers()]);
  if (form.company_id) {
    await Promise.all([loadCustomers(form.company_id), loadApplications(form.company_id)]);
  }
});

function fieldClass(field) {
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

async function loadCompanies() {
  try {
    const { data } = await companyService.list({ per_page: 100 });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
}

async function loadCustomers(companyId) {
  try {
    const { data } = await customerService.list({ company: companyId, per_page: 100 });
    customers.value = data.data?.customers?.items ?? [];
  } catch {
    customers.value = [];
  }
}

async function loadApplications(companyId) {
  try {
    const { data } = await applicationService.list({ company: companyId, per_page: 100 });
    applications.value = data.data?.applications?.items ?? [];
  } catch {
    applications.value = [];
  }
}

async function loadUsers() {
  try {
    const { data } = await userService.list({ per_page: 100 });
    users.value = data.data?.users?.items ?? data.data?.users ?? [];
  } catch {
    users.value = [];
  }
}

function validate() {
  const next = {};

  if (!String(form.company_id || '').trim()) {
    next.company_id = ['Please select a company.'];
  }

  if (!String(form.category || '').trim()) {
    next.category = ['The category field is required.'];
  }

  if (!String(form.priority || '').trim()) {
    next.priority = ['The priority field is required.'];
  }

  if (!String(form.source || '').trim()) {
    next.source = ['The source field is required.'];
  }

  if (props.initial.uuid && !String(form.status || '').trim()) {
    next.status = ['The status field is required.'];
  }

  if (!String(form.subject || '').trim()) {
    next.subject = ['The subject field is required.'];
  }

  if (!String(form.description || '').trim()) {
    next.description = ['The description field is required.'];
  }

  localErrors.value = next;
  return Object.keys(next).length === 0;
}

function onSubmit() {
  if (!validate()) {
    toast.error('Please fix the highlighted fields.', 'Validation Failed');
    return;
  }

  localErrors.value = {};

  const payload = { ...form };
  if (!payload.customer_id) {
    payload.customer_id = null;
  }
  if (!payload.application_id) {
    payload.application_id = null;
  }
  if (!payload.assigned_to) {
    payload.assigned_to = null;
  }
  if (!props.initial.uuid) {
    delete payload.status;
  }
  emit('submit', payload);
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
