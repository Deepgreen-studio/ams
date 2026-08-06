<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Company name</label>
        <input v-model="form.company_name" type="text" class="input" required />
        <p v-if="errors.company_name" class="mt-1 text-xs text-rose-600">{{ errors.company_name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Legal name</label>
        <input v-model="form.legal_name" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Registration number</label>
        <input v-model="form.registration_number" type="text" class="input" />
        <p v-if="errors.registration_number" class="mt-1 text-xs text-rose-600">{{ errors.registration_number[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Tax number</label>
        <input v-model="form.tax_number" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input v-model="form.email" type="email" class="input" />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
        <input v-model="form.phone" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Website</label>
        <input v-model="form.website" type="url" class="input" placeholder="https://" />
        <p v-if="errors.website" class="mt-1 text-xs text-rose-600">{{ errors.website[0] }}</p>
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
        <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
        <textarea v-model="form.address" rows="2" class="input" />
      </div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">City</label><input v-model="form.city" type="text" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">State</label><input v-model="form.state" type="text" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Postal code</label><input v-model="form.postal_code" type="text" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Country</label><input v-model="form.country" type="text" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Timezone</label><input v-model="form.timezone" type="text" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Language</label><input v-model="form.language" type="text" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Currency</label><input v-model="form.currency" type="text" maxlength="3" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Date format</label><input v-model="form.date_format" type="text" class="input" /></div>
      <div><label class="mb-1 block text-sm font-medium text-slate-700">Time format</label><input v-model="form.time_format" type="text" class="input" /></div>
    </div>
    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('cancel')">Cancel</button>
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="loading">
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});

defineEmits(['submit', 'cancel']);

const form = reactive(createForm(props.initial));
watch(() => props.initial, (value) => Object.assign(form, createForm(value)), { deep: true });

function createForm(value = {}) {
  return {
    company_name: value.company_name || '',
    legal_name: value.legal_name || '',
    registration_number: value.registration_number || '',
    tax_number: value.tax_number || '',
    email: value.email || '',
    phone: value.phone || '',
    website: value.website || '',
    address: value.address || '',
    city: value.city || '',
    state: value.state || '',
    postal_code: value.postal_code || '',
    country: value.country || '',
    timezone: value.timezone || 'UTC',
    language: value.language || 'en',
    currency: value.currency || 'USD',
    date_format: value.date_format || 'Y-m-d',
    time_format: value.time_format || 'H:i',
    status: value.status || 'active',
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
