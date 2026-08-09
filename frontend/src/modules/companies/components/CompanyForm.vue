<template>
  <form class="space-y-4" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Company name</label>
        <input
          v-model="form.company_name"
          type="text"
          class="input"
          :class="fieldClass('company_name')"
        />
        <p v-if="displayErrors.company_name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_name[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Legal name</label>
        <input v-model="form.legal_name" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Registration number</label>
        <input
          v-model="form.registration_number"
          type="text"
          class="input"
          :class="fieldClass('registration_number')"
        />
        <p v-if="displayErrors.registration_number" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.registration_number[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Tax number</label>
        <input v-model="form.tax_number" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="input"
          :class="fieldClass('email')"
        />
        <p v-if="displayErrors.email" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.email[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
        <input v-model="form.phone" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Website</label>
        <input
          v-model="form.website"
          type="url"
          class="input"
          placeholder="https://"
          :class="fieldClass('website')"
        />
        <p v-if="displayErrors.website" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.website[0] }}
        </p>
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
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">City</label>
        <input v-model="form.city" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">State</label>
        <input v-model="form.state" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Postal code</label>
        <input v-model="form.postal_code" type="text" class="input" />
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
        <label class="mb-1 block text-sm font-medium text-slate-700">Currency</label>
        <input v-model="form.currency" type="text" maxlength="3" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Date format</label>
        <input v-model="form.date_format" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Time format</label>
        <input v-model="form.time_format" type="text" class="input" />
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
import { computed, reactive, ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});

const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();
const localErrors = ref({});

const form = reactive(createForm(props.initial));
watch(() => props.initial, (value) => Object.assign(form, createForm(value)), { deep: true });

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

const displayErrors = computed(() => ({
  ...localErrors.value,
  ...props.errors,
}));

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

function fieldClass(field) {
  return displayErrors.value?.[field]
    ? 'border-rose-400 focus:border-rose-500'
    : '';
}

function validate() {
  const next = {};

  if (!String(form.company_name || '').trim()) {
    next.company_name = ['The company name field is required.'];
  }

  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    next.email = ['The email must be a valid email address.'];
  }

  if (form.website) {
    try {
      void new URL(form.website);
    } catch {
      next.website = ['The website must be a valid URL.'];
    }
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
  emit('submit', { ...form });
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
