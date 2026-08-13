<template>
  <form class="space-y-4" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
        <SelectBox
          v-model="form.company_id"
          placeholder="Select company"
          :options="companySelectOptions"
          :disabled="Boolean(initial.uuid)"
          :error="Boolean(displayErrors.company_id)"
        />
        <p v-if="displayErrors.company_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Customer</label>
        <SelectBox
          v-model="form.customer_id"
          placeholder="Optional"
          :options="customerSelectOptions"
          :disabled="!form.company_id"
          :error="Boolean(displayErrors.customer_id)"
        />
        <p v-if="displayErrors.customer_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.customer_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Application</label>
        <SelectBox
          v-model="form.application_id"
          placeholder="Optional"
          :options="applicationSelectOptions"
          :disabled="!form.company_id"
          :error="Boolean(displayErrors.application_id)"
        />
        <p v-if="displayErrors.application_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.application_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
        <SelectBox
          v-model="form.category"
          placeholder="Select category"
          :options="categoryOptions"
          :error="Boolean(displayErrors.category)"
        />
        <p v-if="displayErrors.category" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.category[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Priority</label>
        <SelectBox
          v-model="form.priority"
          placeholder="Select priority"
          :options="priorityOptions"
          :error="Boolean(displayErrors.priority)"
        />
        <p v-if="displayErrors.priority" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.priority[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Source</label>
        <SelectBox
          v-model="form.source"
          placeholder="Select source"
          :options="sourceOptions"
          :error="Boolean(displayErrors.source)"
        />
        <p v-if="displayErrors.source" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.source[0] }}
        </p>
      </div>

      <div v-if="initial.uuid">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox
          v-model="form.status"
          placeholder="Select status"
          :options="statusOptions"
          :error="Boolean(displayErrors.status)"
        />
        <p v-if="displayErrors.status" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.status[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Assign to</label>
        <SelectBox
          v-model="form.assigned_to"
          placeholder="Unassigned"
          :options="assigneeSelectOptions"
        />
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject</label>
        <input
          v-model="form.subject"
          type="text"
          maxlength="255"
          class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-0"
          :class="fieldClass('subject')"
        />
        <p v-if="displayErrors.subject" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.subject[0] }}
        </p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="6"
          class="w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-0"
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
        class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
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
import SelectBox from '@/modules/users/components/SelectBox.vue';
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

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const customerSelectOptions = computed(() => [
  { value: '', label: 'Optional' },
  ...filteredCustomers.value.map((customer) => ({
    value: customer.uuid,
    label: customer.display_name || customer.email,
  })),
]);

const applicationSelectOptions = computed(() => [
  { value: '', label: 'Optional' },
  ...filteredApplications.value.map((application) => ({
    value: application.uuid,
    label: application.name,
  })),
]);

const assigneeSelectOptions = computed(() => [
  { value: '', label: 'Unassigned' },
  ...users.value.map((user) => ({
    value: user.uuid,
    label: `${user.full_name} (${user.email})`,
  })),
]);

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

