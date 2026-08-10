<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Owning company</label>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companyOptions"
          :disabled="Boolean(initial.uuid)"
          :error="Boolean(displayErrors.company_id)"
        />
        <p v-if="displayErrors.company_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Customer type</label>
        <SelectBox
          v-model="form.customer_type"
          size="lg"
          :options="typeOptions"
          :error="Boolean(displayErrors.customer_type)"
        />
        <p v-if="displayErrors.customer_type" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.customer_type[0] }}
        </p>
      </div>

      <template v-if="form.customer_type === 'individual'">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">First name</label>
          <input
            v-model="form.first_name"
            type="text"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('first_name')"
          />
          <p v-if="displayErrors.first_name" class="mt-1 text-xs text-rose-600">
            {{ displayErrors.first_name[0] }}
          </p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Last name</label>
          <input
            v-model="form.last_name"
            type="text"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('last_name')"
          />
          <p v-if="displayErrors.last_name" class="mt-1 text-xs text-rose-600">
            {{ displayErrors.last_name[0] }}
          </p>
        </div>
      </template>

      <div v-if="form.customer_type !== 'individual'" class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company name</label>
        <input
          v-model="form.company_name"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('company_name')"
        />
        <p v-if="displayErrors.company_name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_name[0] }}
        </p>
      </div>

      <div v-if="form.customer_type !== 'individual'">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Contact first name</label>
        <input
          v-model="form.first_name"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div v-if="form.customer_type !== 'individual'">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Contact last name</label>
        <input
          v-model="form.last_name"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
        <input
          v-model="form.email"
          type="email"
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
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Industry</label>
        <input
          v-model="form.industry"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Country</label>
        <input
          v-model="form.country"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Timezone</label>
        <SelectBox v-model="form.timezone" size="lg" :options="timezoneOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Language</label>
        <SelectBox v-model="form.language" size="lg" :options="languageOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Notes</label>
        <textarea
          v-model="form.notes"
          rows="3"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useToast } from '@/composables/useToast';
import { companyService } from '@/modules/companies/services/companyService';
import { getTimezoneOptions, LANGUAGE_OPTIONS } from '@/utils/localeOptions';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  hideCompany: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();
const companies = ref([]);
const localErrors = ref({});
const timezoneOptionsBase = getTimezoneOptions();
const form = reactive(createForm(props.initial));

const typeOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'business', label: 'Business' },
  { value: 'enterprise', label: 'Enterprise' },
];

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'pending', label: 'Pending' },
];

const companyOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

watch(
  () => props.initial,
  (value) => Object.assign(form, createForm(value)),
  { deep: true },
);

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

watch(
  () => form.customer_type,
  () => {
    localErrors.value = {};
  },
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

onMounted(async () => {
  if (props.hideCompany) {
    return;
  }

  try {
    const { data } = await companyService.list({
      per_page: 100,
      sort_by: 'company_name',
      sort_dir: 'asc',
    });
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

function fieldClass(field) {
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

function validate() {
  const next = {};

  if (!props.hideCompany && !String(form.company_id || '').trim()) {
    next.company_id = ['Please select an owning company.'];
  }

  if (!String(form.customer_type || '').trim()) {
    next.customer_type = ['The customer type field is required.'];
  }

  if (form.customer_type === 'individual') {
    if (!String(form.first_name || '').trim()) {
      next.first_name = ['First name is required for individual customers.'];
    }
    if (!String(form.last_name || '').trim()) {
      next.last_name = ['Last name is required for individual customers.'];
    }
  } else if (!String(form.company_name || '').trim()) {
    next.company_name = ['Company name is required for business and enterprise customers.'];
  }

  if (!String(form.email || '').trim()) {
    next.email = ['The email field is required.'];
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
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
