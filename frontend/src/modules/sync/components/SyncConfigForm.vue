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
          :error="Boolean(displayErrors.company_id)"
          @change="onCompanyChange"
        />
        <p v-if="displayErrors.company_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.company_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Integration</label>
        <SelectBox
          v-model="form.integration_id"
          size="lg"
          :placeholder="form.company_id || hideCompany ? 'Select integration' : 'Select company first'"
          :options="integrationOptions"
          :disabled="!form.company_id && !hideCompany"
          :error="Boolean(displayErrors.integration_id)"
        />
        <p v-if="displayErrors.integration_id" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.integration_id[0] }}
        </p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="Contacts import"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('name')"
        />
        <p v-if="displayErrors.name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.name[0] }}
        </p>
      </div>

      <div class="flex items-end">
        <label
          class="flex w-full items-center justify-between gap-4 rounded-xl border border-slate-200 bg-zinc-50 px-4 py-3"
        >
          <span>
            <span class="block text-sm font-medium text-slate-900">Enabled</span>
            <span class="mt-0.5 block text-xs text-slate-500">Disabled configs cannot be run.</span>
          </span>
          <input
            v-model="form.is_enabled"
            type="checkbox"
            class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
          />
        </label>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Direction</label>
        <SelectBox v-model="form.direction" size="lg" :options="directionOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Default mode</label>
        <SelectBox v-model="form.default_mode" size="lg" :options="modeOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Trigger</label>
        <SelectBox v-model="form.trigger_type" size="lg" :options="triggerOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Conflict strategy</label>
        <SelectBox v-model="form.conflict_strategy" size="lg" :options="conflictOptions" />
      </div>
      <div v-if="form.trigger_type === 'scheduled'" class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Cron expression</label>
        <input
          v-model="form.schedule_cron"
          type="text"
          placeholder="*/15 * * * *"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 font-mono text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('schedule_cron')"
        />
        <p v-if="displayErrors.schedule_cron" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.schedule_cron[0] }}
        </p>
        <p v-else class="mt-1 text-xs text-slate-400">Standard five-field cron, for example every 15 minutes.</p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Source path</label>
        <input
          v-model="form.source_path"
          type="text"
          placeholder="/api/records"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 font-mono text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Target path</label>
        <input
          v-model="form.target_path"
          type="text"
          placeholder="/api/export"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 font-mono text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Entity type</label>
        <input
          v-model="form.entity_type"
          type="text"
          placeholder="contacts"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Batch size</label>
        <input
          v-model.number="form.batch_size"
          type="number"
          min="1"
          max="500"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('batch_size')"
        />
        <p v-if="displayErrors.batch_size" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.batch_size[0] }}
        </p>
        <p v-else class="mt-1 text-xs text-slate-400">Records processed per batch (1–500).</p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Cursor field</label>
        <input
          v-model="form.cursor_field"
          type="text"
          placeholder="updated_at"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        />
        <p class="mt-1 text-xs text-slate-400">Used for incremental syncs to resume from the last record.</p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Short summary of this synchronization"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Sample records JSON</label>
        <textarea
          v-model="sampleRecordsText"
          rows="6"
          placeholder='[{"id":"1","name":"Ada"}]'
          class="w-full rounded-xl border border-slate-200 bg-zinc-50 px-3.5 py-3 font-mono text-xs text-slate-800 shadow-none outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-0"
          :class="fieldClass('options')"
        />
        <p v-if="displayErrors.options" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.options[0] }}
        </p>
        <p v-else class="mt-1 text-xs text-slate-400">Must be a valid JSON array of records.</p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-6">
      <button
        type="button"
        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
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
import SelectBox from '@/modules/users/components/SelectBox.vue';
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
const sampleRecordsText = ref('');
const localErrors = ref({});
const form = reactive(createForm(props.initial));

const directionOptions = [
  { value: 'import', label: 'Import' },
  { value: 'export', label: 'Export' },
  { value: 'bidirectional', label: 'Bidirectional' },
];

const modeOptions = [
  { value: 'full', label: 'Full' },
  { value: 'incremental', label: 'Incremental' },
];

const triggerOptions = [
  { value: 'manual', label: 'Manual' },
  { value: 'automatic', label: 'Automatic' },
  { value: 'scheduled', label: 'Scheduled' },
];

const conflictOptions = [
  { value: 'overwrite', label: 'Overwrite' },
  { value: 'skip', label: 'Skip' },
  { value: 'merge', label: 'Merge' },
  { value: 'fail', label: 'Fail' },
];

const companyOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const integrationOptions = computed(() =>
  integrations.value.map((item) => ({
    value: item.uuid,
    label: item.name,
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
    const sample = value?.options?.sample_records;
    sampleRecordsText.value = sample ? JSON.stringify(sample, null, 2) : '';
    localErrors.value = {};
    if (form.company_id) loadIntegrations();
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
  if (!props.hideCompany && !props.initial?.uuid) {
    try {
      const { data } = await companyService.list({ per_page: 100, status: 'active' });
      companies.value = data.data?.companies?.items ?? [];
    } catch {
      companies.value = [];
    }
  }
  if (form.company_id) await loadIntegrations();
});

function createForm(value = {}) {
  return {
    company_id: value.company?.uuid || value.company_id || '',
    integration_id: value.integration?.uuid || value.integration_id || '',
    name: value.name || '',
    description: value.description || '',
    direction: value.direction || 'import',
    default_mode: value.default_mode || 'full',
    trigger_type: value.trigger_type || 'manual',
    schedule_cron: value.schedule_cron || '',
    conflict_strategy: value.conflict_strategy || 'overwrite',
    source_path: value.source_path || '',
    target_path: value.target_path || '',
    entity_type: value.entity_type || '',
    batch_size: value.batch_size ?? 100,
    cursor_field: value.cursor_field || 'id',
    is_enabled: value.is_enabled ?? true,
  };
}

function fieldClass(field) {
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

async function loadIntegrations() {
  if (!form.company_id) {
    integrations.value = [];
    return;
  }
  try {
    const { data } = await integrationService.list({ company: form.company_id, per_page: 100 });
    integrations.value = data.data?.integrations?.items ?? [];
  } catch {
    integrations.value = [];
  }
}

function onCompanyChange() {
  form.integration_id = '';
  loadIntegrations();
}

function validate() {
  const next = {};

  if (!props.hideCompany && !String(form.company_id || '').trim()) {
    next.company_id = ['Please select a company.'];
  }

  if (!String(form.integration_id || '').trim()) {
    next.integration_id = ['Please select an integration.'];
  }

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (form.trigger_type === 'scheduled' && !String(form.schedule_cron || '').trim()) {
    next.schedule_cron = ['The cron expression field is required for scheduled syncs.'];
  }

  if (form.batch_size !== null && form.batch_size !== undefined && form.batch_size !== '') {
    const batch = Number(form.batch_size);
    if (!Number.isInteger(batch) || batch < 1 || batch > 500) {
      next.batch_size = ['Batch size must be between 1 and 500.'];
    }
  }

  const trimmed = sampleRecordsText.value.trim();
  if (trimmed) {
    try {
      JSON.parse(trimmed);
    } catch {
      next.options = ['Sample records must be valid JSON.'];
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

  let options = props.initial?.options ? { ...props.initial.options } : {};
  const trimmed = sampleRecordsText.value.trim();
  if (trimmed) {
    options = { ...options, sample_records: JSON.parse(trimmed) };
  } else if (options.sample_records) {
    delete options.sample_records;
  }

  emit('submit', {
    ...form,
    schedule_cron: form.trigger_type === 'scheduled' ? form.schedule_cron : null,
    options,
  });
}
</script>
