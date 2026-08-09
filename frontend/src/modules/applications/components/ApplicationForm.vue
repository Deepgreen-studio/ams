<template>
  <form class="space-y-4" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select
          v-model="form.company_id"
          class="input"
          :class="fieldClass('company_id')"
          :disabled="Boolean(initial.uuid)"
          @change="onCompanyChange"
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
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input
          v-model="form.name"
          type="text"
          class="input"
          :class="fieldClass('name')"
        />
        <p v-if="displayErrors.name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.name[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
        <input
          v-model="form.slug"
          type="text"
          class="input"
          placeholder="auto-generated if empty"
          :class="fieldClass('slug')"
        />
        <p v-if="displayErrors.slug" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.slug[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Platform</label>
        <select
          v-model="form.platform"
          class="input"
          :class="fieldClass('platform')"
        >
          <option value="android">Android</option>
          <option value="ios">iOS</option>
          <option value="web">Web</option>
          <option value="desktop">Desktop (future)</option>
        </select>
        <p v-if="displayErrors.platform" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.platform[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
        <select
          v-model="form.category"
          class="input"
          :class="fieldClass('category')"
        >
          <option value="">Select category</option>
          <option value="business">Business</option>
          <option value="productivity">Productivity</option>
          <option value="utilities">Utilities</option>
          <option value="social">Social</option>
          <option value="education">Education</option>
          <option value="health">Health</option>
          <option value="finance">Finance</option>
          <option value="entertainment">Entertainment</option>
          <option value="other">Other</option>
        </select>
        <p v-if="displayErrors.category" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.category[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="archived">Archived</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Visibility</label>
        <select v-model="form.visibility" class="input">
          <option value="private">Private</option>
          <option value="internal">Internal</option>
          <option value="public">Public</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Integration</label>
        <select
          v-model="form.integration_id"
          class="input"
          :class="fieldClass('integration_id')"
          :disabled="!form.company_id && !initial.uuid"
        >
          <option value="">None</option>
          <option v-for="integration in integrations" :key="integration.uuid" :value="integration.uuid">
            {{ integration.name }}
          </option>
        </select>
        <p v-if="displayErrors.integration_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.integration_id[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Current version</label>
        <input v-model="form.current_version" type="text" class="input" placeholder="1.0.0" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Minimum supported version</label>
        <input v-model="form.minimum_supported_version" type="text" class="input" placeholder="1.0.0" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Icon URL</label>
        <input
          v-model="form.icon"
          type="url"
          class="input"
          placeholder="https://"
          :class="fieldClass('icon')"
        />
        <p v-if="displayErrors.icon" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.icon[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Banner URL</label>
        <input
          v-model="form.banner"
          type="url"
          class="input"
          placeholder="https://"
          :class="fieldClass('banner')"
        />
        <p v-if="displayErrors.banner" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.banner[0] }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="3" class="input" />
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
const localErrors = ref({});
const form = reactive(createForm(props.initial));

watch(() => props.initial, (value) => {
  Object.assign(form, createForm(value));
  loadIntegrations(form.company_id || value.company?.uuid);
}, { deep: true });

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
    const { data } = await integrationService.list({ company: companyUuid, per_page: 100, status: 'active' });
    integrations.value = data.data?.integrations?.items ?? [];
  } catch {
    integrations.value = [];
  }
}

function createForm(value = {}) {
  return {
    company_id: value.company?.uuid || value.company_id || '',
    integration_id: value.integration?.uuid || value.integration_id || '',
    name: value.name || '',
    slug: value.slug || '',
    description: value.description || '',
    platform: value.platform || 'android',
    category: value.category || '',
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

function validate() {
  const next = {};

  if (!props.hideCompany && !String(form.company_id || '').trim()) {
    next.company_id = ['Please select a company.'];
  }

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (!String(form.platform || '').trim()) {
    next.platform = ['The platform field is required.'];
  }

  if (form.icon && !isValidUrl(form.icon)) {
    next.icon = ['The icon must be a valid URL.'];
  }

  if (form.banner && !isValidUrl(form.banner)) {
    next.banner = ['The banner must be a valid URL.'];
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
