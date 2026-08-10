<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company name</label>
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
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Legal name</label>
        <input
          v-model="form.legal_name"
          type="text"
          placeholder="Acme Corporation Ltd"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Registration number</label>
        <input
          v-model="form.registration_number"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('registration_number')"
        />
        <p v-if="displayErrors.registration_number" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.registration_number[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Tax number</label>
        <input
          v-model="form.tax_number"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
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
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
        <input
          v-model="form.phone"
          type="text"
          placeholder="+15551234567"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('phone')"
        />
        <p v-if="displayErrors.phone" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.phone[0] }}
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
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Address</label>
        <textarea
          v-model="form.address"
          rows="3"
          placeholder="Street address"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">City</label>
        <input
          v-model="form.city"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">State</label>
        <input
          v-model="form.state"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Postal code</label>
        <input
          v-model="form.postal_code"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Country</label>
        <input
          v-model="form.country"
          type="text"
          placeholder="GB"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Timezone</label>
        <SearchableSelect
          v-model="form.timezone"
          :options="timezoneOptions"
          placeholder="Select timezone"
          search-placeholder="Search timezone…"
        />
        <p v-if="displayErrors.timezone" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.timezone[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Language</label>
        <SearchableSelect
          v-model="form.language"
          :options="languageOptions"
          placeholder="Select language"
          search-placeholder="Search language…"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Currency</label>
        <SelectBox v-model="form.currency" size="lg" :options="currencyOptions" />
        <p v-if="displayErrors.currency" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.currency[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Date format</label>
        <SelectBox v-model="form.date_format" size="lg" :options="dateFormatOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Time format</label>
        <SelectBox v-model="form.time_format" size="lg" :options="timeFormatOptions" />
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
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useToast } from '@/composables/useToast';
import { getTimezoneOptions, LANGUAGE_OPTIONS } from '@/utils/localeOptions';

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
const timezoneOptionsBase = getTimezoneOptions();

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'pending', label: 'Pending' },
];

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

const dateFormatOptionsBase = [
  { value: 'Y-m-d', label: 'Y-m-d (2026-08-10)' },
  { value: 'd/m/Y', label: 'd/m/Y (10/08/2026)' },
  { value: 'm/d/Y', label: 'm/d/Y (08/10/2026)' },
  { value: 'd-m-Y', label: 'd-m-Y (10-08-2026)' },
  { value: 'd M Y', label: 'd M Y (10 Aug 2026)' },
];

const timeFormatOptionsBase = [
  { value: 'H:i', label: '24-hour (14:30)' },
  { value: 'h:i A', label: '12-hour (02:30 PM)' },
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

const timezoneOptions = computed(() => withCurrentOption(timezoneOptionsBase, form.timezone));
const languageOptions = computed(() => withCurrentOption(LANGUAGE_OPTIONS, form.language));
const currencyOptions = computed(() => withCurrentOption(currencyOptionsBase, form.currency));
const dateFormatOptions = computed(() => withCurrentOption(dateFormatOptionsBase, form.date_format));
const timeFormatOptions = computed(() => withCurrentOption(timeFormatOptionsBase, form.time_format));

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

  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    next.email = ['The email must be a valid email address.'];
  }

  if (form.phone && !/^\+?[0-9\s\-()]{7,30}$/.test(form.phone)) {
    next.phone = ['The phone format is invalid.'];
  }

  if (form.website) {
    try {
      void new URL(form.website);
    } catch {
      next.website = ['The website must be a valid URL.'];
    }
  }

  if (form.currency && String(form.currency).length !== 3) {
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
