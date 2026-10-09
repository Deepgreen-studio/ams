<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div v-if="!hideCompany">
        <FormLabel required>Company</FormLabel>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companyOptions"
          :disabled="Boolean(initial.uuid)"
          :error="Boolean(displayErrors.company_id)"
          @change="onCompanyChange"
        />
        <p class="mt-1 text-xs text-slate-500">This company owns the application.</p>
        <FieldError :message="displayErrors.company_id?.[0] || ''" />
      </div>

      <div>
        <FormLabel required>Name</FormLabel>
        <input
          v-model="form.name"
          type="text"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('name')"
        />
        <FieldError :message="displayErrors.name?.[0] || ''" />
      </div>

      <div>
        <FormLabel required>Slug</FormLabel>
        <input
          v-model="form.slug"
          type="text"
          placeholder="easycare"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('slug')"
        />
        <FieldError :message="displayErrors.slug?.[0] || ''" />
      </div>

      <div>
        <FormLabel required>Platform</FormLabel>
        <SelectBox
          v-model="form.platforms"
          size="lg"
          multiple
          placeholder="Select platforms"
          :options="platformOptions"
          :error="Boolean(platformError)"
        />
        <p class="mt-1 text-xs text-slate-500">Select every platform this application supports.</p>
        <FieldError :message="platformError" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
        <SelectBox
          v-model="form.category"
          size="lg"
          placeholder="Select category"
          :options="categoryOptions"
          :error="Boolean(displayErrors.category)"
        />
        <FieldError :message="displayErrors.category?.[0] || ''" />
        <div v-if="form.category === 'other'" class="mt-3">
          <FormLabel required>Other category</FormLabel>
          <input
            v-model="form.category_custom"
            type="text"
            placeholder="Enter category"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('category_custom')"
          />
          <FieldError :message="displayErrors.category_custom?.[0] || ''" />
        </div>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Visibility</label>
        <SelectBox v-model="form.visibility" size="lg" :options="visibilityOptions" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Integration</label>
        <SelectBox
          v-model="form.integration_id"
          size="lg"
          placeholder="None"
          :options="integrationOptions"
          :disabled="!form.company_id && !initial.uuid"
          :error="Boolean(displayErrors.integration_id)"
        />
        <FieldError :message="displayErrors.integration_id?.[0] || ''" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Current Version</label>
        <input
          v-model="form.current_version"
          type="text"
          placeholder="1.0.0"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700"
          >Minimum Supported Version</label
        >
        <input
          v-model="form.minimum_supported_version"
          type="text"
          placeholder="1.0.0"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Icon URL</label>
        <input
          v-model="form.icon"
          type="url"
          placeholder="https://"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('icon')"
        />
        <FieldError :message="displayErrors.icon?.[0] || ''" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Banner URL</label>
        <input
          v-model="form.banner"
          type="url"
          placeholder="https://"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('banner')"
        />
        <FieldError :message="displayErrors.banner?.[0] || ''" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
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
import FieldError from '@/components/ui/FieldError.vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useFieldErrors } from '@/composables/useFieldErrors';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import FormLabel from '@/components/ui/FormLabel.vue';
import { useToast } from '@/composables/useToast';
import { companyService } from '@/modules/companies/services/companyService';
import { integrationService } from '@/modules/integrations/services/integrationService';

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
const integrations = ref([]);
const form = reactive(createForm(props.initial));
const { localErrors, displayErrors } = useFieldErrors(form, () => props.errors, () => collectErrors());

const platformOptions = [
  { value: 'android', label: 'Android' },
  { value: 'ios', label: 'iOS' },
  { value: 'web', label: 'Web' },
  { value: 'desktop', label: 'Desktop' },
];

const categoryOptions = [
  { value: '', label: 'Select category' },
  { value: 'business', label: 'Business' },
  { value: 'productivity', label: 'Productivity' },
  { value: 'utilities', label: 'Utilities' },
  { value: 'social', label: 'Social' },
  { value: 'education', label: 'Education' },
  { value: 'health', label: 'Health' },
  { value: 'finance', label: 'Finance' },
  { value: 'entertainment', label: 'Entertainment' },
  { value: 'other', label: 'Other' },
];

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'archived', label: 'Archived' },
];

const visibilityOptions = [
  { value: 'private', label: 'Private' },
  { value: 'internal', label: 'Internal' },
  { value: 'public', label: 'Public' },
];

const companyOptions = computed(() =>
  companies.value
    .filter((company) => company.status === 'active')
    .map((company) => ({
      value: company.uuid,
      label: company.company_name,
    })),
);

const integrationOptions = computed(() => [
  { value: '', label: 'None' },
  ...integrations.value.map((integration) => ({
    value: integration.uuid,
    label: integration.name,
  })),
]);

const platformError = computed(
  () =>
    displayErrors.value.platforms?.[0]
    || displayErrors.value['platforms.0']?.[0]
    || displayErrors.value.platform?.[0]
    || '',
);

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, createForm(value));
    loadIntegrations(form.company_id || value.company?.uuid);
  },
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

onMounted(async () => {
  if (!props.hideCompany && !props.initial?.uuid) {
    try {
      const { data } = await companyService.list({ per_page: 100, status: 'active' });
      companies.value = data.data?.companies?.items ?? [];
    } catch {
      companies.value = [];
    }
  }

  const companyUuid = form.company_id || props.initial?.company?.uuid;
  if (companyUuid) {
    await loadIntegrations(companyUuid);
  }
});

async function onCompanyChange() {
  form.integration_id = '';
  await loadIntegrations(form.company_id);
}

async function loadIntegrations(companyUuid) {
  if (!companyUuid) {
    integrations.value = [];
    return;
  }

  try {
    const { data } = await integrationService.list({
      company: companyUuid,
      per_page: 100,
      status: 'active',
    });
    integrations.value = data.data?.integrations?.items ?? [];
  } catch {
    integrations.value = [];
  }
}

function normalizePlatforms(value = {}) {
  if (Array.isArray(value.platforms) && value.platforms.length) {
    return [...value.platforms];
  }

  if (value.platform) {
    return [value.platform];
  }

  return ['android'];
}

function createForm(value = {}) {
  return {
    company_id: value.company?.uuid || value.company_id || '',
    integration_id: value.integration?.uuid || value.integration_id || '',
    name: value.name || '',
    slug: value.slug || '',
    description: value.description || '',
    platform: value.platform || 'android',
    platforms: normalizePlatforms(value),
    category: value.category || '',
    category_custom: value.category_custom || '',
    icon: value.icon || '',
    banner: value.banner || '',
    current_version: value.current_version || '',
    minimum_supported_version: value.minimum_supported_version || '',
    status: value.status || 'draft',
    visibility: value.visibility || 'private',
  };
}

function fieldClass(field) {
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

function isValidUrl(value) {
  try {
    void new URL(value);
    return true;
  } catch {
    return false;
  }
}

function collectErrors() {
  const next = {};

  if (!props.hideCompany && !String(form.company_id || '').trim()) {
    next.company_id = ['Please select a company.'];
  }

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (!String(form.slug || '').trim()) {
    next.slug = ['The slug field is required.'];
  } else if (!/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/.test(String(form.slug).trim())) {
    next.slug = ['The slug may only contain letters, numbers, and dashes.'];
  }

  if (form.category === 'other' && !String(form.category_custom || '').trim()) {
    next.category_custom = ['Enter the other category.'];
  }

  if (!Array.isArray(form.platforms) || !form.platforms.length) {
    next.platforms = ['Select at least one platform.'];
  }

  if (form.icon && !isValidUrl(form.icon)) {
    next.icon = ['The icon must be a valid URL.'];
  }

  if (form.banner && !isValidUrl(form.banner)) {
    next.banner = ['The banner must be a valid URL.'];
  }

  return next;
}

function validate() {
  const next = collectErrors();
  localErrors.value = next;
  return Object.keys(next).length === 0;
}

function onSubmit() {
  if (!validate()) {
    toast.error('Please fix the highlighted fields.', 'Validation Failed');
    return;
  }

  localErrors.value = {};
  const payload = { ...form, platforms: [...form.platforms] };
  payload.platform = payload.platforms[0];
  if (payload.category !== 'other') {
    payload.category_custom = '';
  }
  emit('submit', payload);
}
</script>
