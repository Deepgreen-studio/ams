<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
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
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="EasyCare Support Replies"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('name')"
        />
        <p v-if="displayErrors.name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.name[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Direction</label>
        <SelectBox
          v-model="form.direction"
          size="lg"
          :options="directionOptions"
          :disabled="Boolean(initial.uuid)"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
      </div>

      <div v-if="form.direction === 'outgoing'" class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Destination URL</label>
        <input
          v-model="form.url"
          type="url"
          placeholder="https://"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('url')"
        />
        <p v-if="displayErrors.url" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.url[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Signature algorithm</label>
        <SelectBox v-model="form.signature_algorithm" size="lg" :options="signatureOptions" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Signature header</label>
        <input
          v-model="form.signature_header"
          type="text"
          placeholder="X-AMS-Signature"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Timeout (seconds)</label>
        <input
          v-model.number="form.timeout"
          type="number"
          min="1"
          max="300"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('timeout')"
        />
        <p v-if="displayErrors.timeout" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.timeout[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Retry attempts</label>
        <input
          v-model.number="form.retry_attempts"
          type="number"
          min="1"
          max="10"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('retry_attempts')"
        />
        <p v-if="displayErrors.retry_attempts" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.retry_attempts[0] }}
        </p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">
          Subscribed events (comma-separated)
        </label>
        <input
          v-model="eventsText"
          type="text"
          placeholder="webhook.test, integration.created"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Short summary of this webhook"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div v-if="initial.uuid" class="md:col-span-2">
        <label
          class="inline-flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 transition hover:bg-zinc-50"
        >
          <input
            v-model="form.rotate_secret"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
          />
          Rotate webhook secret
        </label>
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
const eventsText = ref('');
const localErrors = ref({});
const form = reactive(createForm(props.initial));

const directionOptions = [
  { value: 'outgoing', label: 'Outgoing' },
  { value: 'incoming', label: 'Incoming' },
];

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'paused', label: 'Paused' },
  { value: 'disabled', label: 'Disabled' },
];

const signatureOptions = [
  { value: 'hmac_sha256', label: 'HMAC SHA-256' },
  { value: 'hmac_sha1', label: 'HMAC SHA-1' },
  { value: 'none', label: 'None' },
];

const companyOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const displayErrors = computed(() => ({
  ...localErrors.value,
  ...props.errors,
}));

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, createForm(value));
    eventsText.value = (value.subscribed_events || []).join(', ');
    localErrors.value = {};
  },
  { deep: true, immediate: true },
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

onMounted(async () => {
  if (props.hideCompany || props.initial?.uuid) return;
  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function createForm(value = {}) {
  return {
    company_id: value.company?.uuid || value.company_id || '',
    name: value.name || '',
    direction: value.direction || 'outgoing',
    status: value.status || 'inactive',
    url: value.url || '',
    description: value.description || '',
    signature_algorithm: value.signature_algorithm || 'hmac_sha256',
    signature_header: value.signature_header || 'X-AMS-Signature',
    timeout: value.timeout ?? 30,
    retry_attempts: value.retry_attempts ?? 3,
    rotate_secret: false,
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

  if (form.direction === 'outgoing') {
    if (!String(form.url || '').trim()) {
      next.url = ['The destination URL field is required.'];
    } else if (!isValidUrl(form.url)) {
      next.url = ['The destination URL must be a valid URL.'];
    }
  }

  if (form.timeout !== null && form.timeout !== undefined && form.timeout !== '') {
    const timeout = Number(form.timeout);
    if (!Number.isInteger(timeout) || timeout < 1 || timeout > 300) {
      next.timeout = ['Timeout must be between 1 and 300 seconds.'];
    }
  }

  if (
    form.retry_attempts !== null &&
    form.retry_attempts !== undefined &&
    form.retry_attempts !== ''
  ) {
    const retries = Number(form.retry_attempts);
    if (!Number.isInteger(retries) || retries < 1 || retries > 10) {
      next.retry_attempts = ['Retry attempts must be between 1 and 10.'];
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

  const subscribed_events = eventsText.value
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);

  emit('submit', {
    ...form,
    subscribed_events,
    url: form.direction === 'outgoing' ? form.url : null,
  });
}
</script>
