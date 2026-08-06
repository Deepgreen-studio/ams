<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select v-model="form.company_id" class="input" required @change="loadIntegrations">
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">{{ company.company_name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Integration</label>
        <select v-model="form.integration_id" class="input" required :disabled="!form.company_id">
          <option value="" disabled>Select integration</option>
          <option v-for="item in integrations" :key="item.uuid" :value="item.uuid">{{ item.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input v-model="form.name" type="text" class="input" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Direction</label>
        <select v-model="form.direction" class="input">
          <option value="import">Import</option>
          <option value="export">Export</option>
          <option value="bidirectional">Bidirectional</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Default mode</label>
        <select v-model="form.default_mode" class="input">
          <option value="full">Full</option>
          <option value="incremental">Incremental</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Trigger</label>
        <select v-model="form.trigger_type" class="input">
          <option value="manual">Manual</option>
          <option value="automatic">Automatic</option>
          <option value="scheduled">Scheduled</option>
        </select>
      </div>
      <div v-if="form.trigger_type === 'scheduled'">
        <label class="mb-1 block text-sm font-medium text-slate-700">Cron expression</label>
        <input v-model="form.schedule_cron" type="text" class="input" placeholder="*/15 * * * *" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Conflict strategy</label>
        <select v-model="form.conflict_strategy" class="input">
          <option value="overwrite">Overwrite</option>
          <option value="skip">Skip</option>
          <option value="merge">Merge</option>
          <option value="fail">Fail</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Source path</label>
        <input v-model="form.source_path" type="text" class="input" placeholder="/api/records" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Target path</label>
        <input v-model="form.target_path" type="text" class="input" placeholder="/api/export" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Entity type</label>
        <input v-model="form.entity_type" type="text" class="input" placeholder="contacts" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Batch size</label>
        <input v-model.number="form.batch_size" type="number" min="1" max="500" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Cursor field</label>
        <input v-model="form.cursor_field" type="text" class="input" placeholder="updated_at" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="3" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Sample records JSON (local testing)</label>
        <textarea v-model="sampleRecordsText" rows="4" class="input font-mono text-xs" placeholder='[{"id":"1","name":"Ada"}]' />
      </div>
      <div class="md:col-span-2">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.is_enabled" type="checkbox" class="rounded border-slate-300" />
          Enabled
        </label>
      </div>
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
import { onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { integrationService } from '@/modules/integrations/services/integrationService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  hideCompany: { type: Boolean, default: false },
});
const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const integrations = ref([]);
const sampleRecordsText = ref('');
const form = reactive(createForm(props.initial));

watch(() => props.initial, (value) => {
  Object.assign(form, createForm(value));
  const sample = value?.options?.sample_records;
  sampleRecordsText.value = sample ? JSON.stringify(sample, null, 2) : '';
  if (form.company_id) loadIntegrations();
}, { deep: true, immediate: true });

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

function onSubmit() {
  let options = props.initial?.options ? { ...props.initial.options } : {};
  const trimmed = sampleRecordsText.value.trim();
  if (trimmed) {
    try {
      options = { ...options, sample_records: JSON.parse(trimmed) };
    } catch {
      emit('submit', null);
      return;
    }
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

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
  background: white;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
