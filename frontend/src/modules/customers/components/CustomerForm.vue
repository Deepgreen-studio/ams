<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1 block text-sm font-medium text-slate-700">Owning company</label>
        <select v-model="form.company_id" class="input" required :disabled="Boolean(initial.uuid)">
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
        <p v-if="errors.company_id" class="mt-1 text-xs text-rose-600">{{ errors.company_id[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Customer type</label>
        <select v-model="form.customer_type" class="input" required>
          <option value="individual">Individual</option>
          <option value="business">Business</option>
          <option value="enterprise">Enterprise</option>
        </select>
        <p v-if="errors.customer_type" class="mt-1 text-xs text-rose-600">{{ errors.customer_type[0] }}</p>
      </div>

      <template v-if="form.customer_type === 'individual'">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">First name</label>
          <input v-model="form.first_name" type="text" class="input" required />
          <p v-if="errors.first_name" class="mt-1 text-xs text-rose-600">{{ errors.first_name[0] }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Last name</label>
          <input v-model="form.last_name" type="text" class="input" required />
          <p v-if="errors.last_name" class="mt-1 text-xs text-rose-600">{{ errors.last_name[0] }}</p>
        </div>
      </template>

      <div v-if="form.customer_type !== 'individual'" class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company name</label>
        <input v-model="form.company_name" type="text" class="input" required />
        <p v-if="errors.company_name" class="mt-1 text-xs text-rose-600">{{ errors.company_name[0] }}</p>
      </div>

      <div v-if="form.customer_type !== 'individual'">
        <label class="mb-1 block text-sm font-medium text-slate-700">Contact first name</label>
        <input v-model="form.first_name" type="text" class="input" />
      </div>
      <div v-if="form.customer_type !== 'individual'">
        <label class="mb-1 block text-sm font-medium text-slate-700">Contact last name</label>
        <input v-model="form.last_name" type="text" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input v-model="form.email" type="email" class="input" required />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
        <input v-model="form.phone" type="text" class="input" />
        <p v-if="errors.phone" class="mt-1 text-xs text-rose-600">{{ errors.phone[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Website</label>
        <input v-model="form.website" type="url" class="input" placeholder="https://" />
        <p v-if="errors.website" class="mt-1 text-xs text-rose-600">{{ errors.website[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Industry</label>
        <input v-model="form.industry" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Country</label>
        <input v-model="form.country" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timezone</label>
        <input v-model="form.timezone" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Language</label>
        <input v-model="form.language" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
          <option value="pending">Pending</option>
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
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  hideCompany: { type: Boolean, default: false },
});

defineEmits(['submit', 'cancel']);

const companies = ref([]);
const form = reactive(createForm(props.initial));

watch(
  () => props.initial,
  (value) => Object.assign(form, createForm(value)),
  { deep: true }
);

onMounted(async () => {
  if (props.hideCompany) {
    return;
  }

  try {
    const { data } = await companyService.list({ per_page: 100, sort_by: 'company_name', sort_dir: 'asc' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function createForm(value = {}) {
  return {
    company_id: value.company?.uuid || value.company_id || '',
    customer_type: value.customer_type || 'individual',
    first_name: value.first_name || '',
    last_name: value.last_name || '',
    company_name: value.company_name || '',
    email: value.email || '',
    phone: value.phone || '',
    website: value.website || '',
    industry: value.industry || '',
    country: value.country || '',
    timezone: value.timezone || 'UTC',
    language: value.language || 'en',
    status: value.status || 'active',
    notes: value.notes || '',
  };
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
