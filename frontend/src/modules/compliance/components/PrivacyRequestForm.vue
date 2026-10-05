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
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Customer</label>
        <SelectBox
          v-model="form.customer_id"
          size="lg"
          placeholder="Not linked"
          :options="customerSelectOptions"
          :disabled="loading || !form.company_id"
          :error="Boolean(fieldError('customer_id'))"
        />
        <p v-if="fieldError('customer_id')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('customer_id') }}
        </p>
        <p v-else class="mt-1 text-xs text-slate-500">
          Optional. Choosing a customer fills the requester details and links this request.
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Request Type</label>
        <SelectBox
          v-model="form.request_type"
          size="lg"
          :options="typeOptions"
          :disabled="loading"
          :error="Boolean(fieldError('request_type'))"
        />
        <p v-if="fieldError('request_type')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('request_type') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Requester Name</label>
        <input
          v-model="form.requester_name"
          type="text"
          class="input"
          required
          maxlength="255"
          placeholder="Full name"
          :disabled="loading"
        />
        <p v-if="fieldError('requester_name')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('requester_name') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Requester Email</label>
        <input
          v-model="form.requester_email"
          type="email"
          class="input"
          required
          maxlength="255"
          placeholder="name@example.com"
          :disabled="loading"
        />
        <p v-if="fieldError('requester_email')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('requester_email') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Requester Phone</label>
        <PhoneInput
          v-model="form.requester_phone"
          :disabled="loading"
          :error="Boolean(fieldError('requester_phone'))"
        />
        <p v-if="fieldError('requester_phone')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('requester_phone') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Due Date</label>
        <input
          v-model="form.due_date"
          type="date"
          class="input"
          :disabled="loading"
        />
        <p v-if="fieldError('due_date')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('due_date') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Assign Officer</label>
        <SelectBox
          v-model="form.assigned_to"
          size="lg"
          placeholder="Unassigned"
          :options="officerSelectOptions"
          :disabled="loading"
        />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="5"
          class="input"
          placeholder="Optional context for the DSAR intake."
          :disabled="loading"
        />
        <p v-if="fieldError('description')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('description') }}
        </p>
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
        {{ loading ? 'Saving…' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import { companyService } from '@/modules/companies/services/companyService';
import { customerService } from '@/modules/customers/services/customerService';
import { userService } from '@/modules/users/services/userService';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  fieldErrors: { type: Object, default: () => ({}) },
  submitLabel: { type: String, default: 'Create request' },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const customers = ref([]);
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
  customer_id: '',
  request_type: 'access_request',
  requester_name: '',
  requester_email: '',
  requester_phone: '',
  assigned_to: '',
  due_date: '',
  description: '',
});

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const customerSelectOptions = computed(() => [
  { value: '', label: 'Not linked' },
  ...customers.value.map((customer) => ({
    value: customer.uuid,
    label: customer.email
      ? `${customer.display_name || customer.email} (${customer.email})`
      : (customer.display_name || customer.uuid),
  })),
]);

const officerSelectOptions = computed(() => [
  { value: '', label: 'Unassigned' },
  ...users.value.map((user) => ({
    value: user.uuid,
    label: user.email ? `${user.full_name} (${user.email})` : user.full_name,
  })),
]);

const canSubmit = computed(() =>
  Boolean(form.company_id && form.request_type && form.requester_name && form.requester_email),
);

function fieldError(key) {
  const value = props.fieldErrors?.[key];
  return Array.isArray(value) ? value[0] : value || '';
}

watch(
  () => form.company_id,
  async (companyId, previous) => {
    if (previous && companyId !== previous) {
      form.customer_id = '';
    }

    await loadCustomers(companyId);
  },
);

watch(
  () => form.customer_id,
  (customerId) => {
    const customer = customers.value.find((item) => item.uuid === customerId);
    if (!customer) {
      return;
    }

    form.requester_name = customer.display_name || '';
    form.requester_email = customer.email || '';
    form.requester_phone = customer.phone || '';
  },
);

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

async function loadCustomers(companyId) {
  if (!companyId) {
    customers.value = [];
    return;
  }

  try {
    const { data } = await customerService.list({ company: companyId, per_page: 100 });
    customers.value = data.data?.customers?.items ?? [];
  } catch {
    customers.value = [];
  }
}

function onSubmit() {
  if (!canSubmit.value || props.loading) {
    return;
  }

  emit('submit', {
    company_id: form.company_id,
    customer_id: form.customer_id || null,
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
