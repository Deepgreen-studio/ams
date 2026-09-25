<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div>
        <FormLabel required>Company Name</FormLabel>
        <input
          v-model="form.company_name"
          type="text"
          placeholder="Acme Corporation"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('company_name')"
        />
        <p v-if="displayErrors.company_name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_name[0] }}
        </p>
      </div>
      <div>
        <FormLabel required>Company Code</FormLabel>
        <input
          v-model="form.registration_number"
          type="text"
          placeholder="ACME-001"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('registration_number')"
        />
        <p v-if="displayErrors.registration_number" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.registration_number[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Tax Number</label>
        <input
          v-model="form.tax_number"
          type="text"
          placeholder="GB123456789"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <FormLabel required>Email</FormLabel>
        <input
          v-model="form.email"
          type="email"
          placeholder="admin@company.com"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('email')"
        />
        <p v-if="displayErrors.email" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.email[0] }}
        </p>
      </div>
      <div>
        <FormLabel required>Phone</FormLabel>
        <PhoneInput
          v-model="form.phone"
          :error="Boolean(displayErrors.phone)"
        />
        <p v-if="displayErrors.phone" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.phone[0] }}
        </p>
      </div>
      <div>
        <FormLabel required>Currency</FormLabel>
        <SelectBox v-model="form.currency" size="lg" :options="currencyOptions" />
        <p v-if="displayErrors.currency" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.currency[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
      </div>
      <div>
        <FormLabel required>Country</FormLabel>
        <SearchableSelect
          v-model="form.country"
          :options="countryOptions"
          placeholder="Select country"
          search-placeholder="Search country"
          :button-class="countryButtonClass"
        />
        <p v-if="displayErrors.country" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.country[0] }}
        </p>
      </div>
      <div>
        <FormLabel required>State</FormLabel>
        <input
          v-model="form.state"
          type="text"
          placeholder="England"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('state')"
        />
        <p v-if="displayErrors.state" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.state[0] }}
        </p>
      </div>
      <div>
        <FormLabel required>City</FormLabel>
        <input
          v-model="form.city"
          type="text"
          placeholder="London"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('city')"
        />
        <p v-if="displayErrors.city" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.city[0] }}
        </p>
      </div>
      <div>
        <FormLabel required>Address</FormLabel>
        <input
          v-model="form.address"
          type="text"
          placeholder="Street address"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('address')"
        />
        <p v-if="displayErrors.address" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.address[0] }}
        </p>
      </div>
      <div>
        <FormLabel required>Postal Code</FormLabel>
        <input
          v-model="form.postal_code"
          type="text"
          placeholder="SW1A 1AA"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('postal_code')"
        />
        <p v-if="displayErrors.postal_code" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.postal_code[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Website</label>
        <input
          v-model="form.website"
          type="url"
          placeholder="https://"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('website')"
        />
        <p v-if="displayErrors.website" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.website[0] }}
        </p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
      <button
        type="button"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import FormLabel from '@/components/ui/FormLabel.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useToast } from '@/composables/useToast';
import { getPhoneCountries, isValidE164, PHONE_INVALID_MESSAGE } from '@/utils/phone';

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

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'pending', label: 'Pending' },
];

const countryOptionsBase = getPhoneCountries()
  .map((country) => ({ value: country.iso, label: country.name }))
  .sort((a, b) => a.label.localeCompare(b.label));

const currencyOptionsBase = [
  { value: 'USD', label: 'USD — US Dollar' },
  { value: 'GBP', label: 'GBP — British Pound' },
  { value: 'EUR', label: 'EUR — Euro' },
  { value: 'CAD', label: 'CAD — Canadian Dollar' },
  { value: 'AUD', label: 'AUD — Australian Dollar' },
  { value: 'INR', label: 'INR — Indian Rupee' },
  { value: 'BDT', label: 'BDT — Bangladeshi Taka' },
  { value: 'JPY', label: 'JPY — Japanese Yen' },
  { value: 'CNY', label: 'CNY — Chinese Yuan' },
  { value: 'SGD', label: 'SGD — Singapore Dollar' },
  { value: 'AED', label: 'AED — UAE Dirham' },
];

const form = reactive(createForm(props.initial));

watch(() => props.initial, (value) => Object.assign(form, createForm(value)), { deep: true });

watch(
  () => props.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  },
);

watch(
  () => props.errors,
  () => {
    localErrors.value = {};
  },
  { deep: true },
);

const displayErrors = computed(() => ({
  ...localErrors.value,
  ...props.errors,
}));

function withCurrentOption(options, current) {
  if (current && !options.some((option) => option.value === current)) {
    return [{ value: current, label: current }, ...options];
  }
  return options;
}

const countryOptions = computed(() => withCurrentOption(countryOptionsBase, form.country));
const countryButtonClass = computed(() =>
  [
    'h-12 w-full rounded-xl border bg-white px-3.5 text-sm shadow-none focus:border-brand-500 focus:outline-none focus:ring-0',
    displayErrors.value.country ? 'border-rose-400' : 'border-slate-200',
  ].join(' '),
);
const currencyOptions = computed(() => withCurrentOption(currencyOptionsBase, form.currency));

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
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

function validate() {
  const next = {};

  if (!String(form.company_name || '').trim()) {
    next.company_name = ['The company name field is required.'];
  }

  if (!String(form.registration_number || '').trim()) {
    next.registration_number = ['The company code field is required.'];
  }

  if (!String(form.email || '').trim()) {
    next.email = ['The email field is required.'];
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    next.email = ['The email must be a valid email address.'];
  }

  if (!String(form.phone || '').trim()) {
    next.phone = ['The phone field is required.'];
  } else if (!isValidE164(form.phone)) {
    next.phone = [PHONE_INVALID_MESSAGE];
  }

  if (!String(form.address || '').trim()) {
    next.address = ['The address field is required.'];
  }

  if (!String(form.city || '').trim()) {
    next.city = ['The city field is required.'];
  }

  if (!String(form.state || '').trim()) {
    next.state = ['The state field is required.'];
  }

  if (!String(form.postal_code || '').trim()) {
    next.postal_code = ['The postal code field is required.'];
  }

  if (!String(form.country || '').trim()) {
    next.country = ['The country field is required.'];
  }

  if (form.website) {
    try {
      void new URL(form.website);
    } catch {
      next.website = ['The website must be a valid URL.'];
    }
  }

  if (!String(form.currency || '').trim()) {
    next.currency = ['The currency field is required.'];
  } else if (String(form.currency).length !== 3) {
    next.currency = ['The currency must be a 3-letter code.'];
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
