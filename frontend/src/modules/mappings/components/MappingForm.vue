<template>
  <form class="space-y-6" @submit.prevent="onSubmit">
    <div v-if="error || formError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error || formError }}</div>

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
        <select v-model="form.integration_id" class="input" required :disabled="!form.company_id && !hideCompany">
          <option value="" disabled>Select integration</option>
          <option v-for="item in integrations" :key="item.uuid" :value="item.uuid">{{ item.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input v-model="form.name" type="text" class="input" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Source entity</label>
        <input v-model="form.source_entity" type="text" class="input" placeholder="EasyCarbs" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Target entity</label>
        <input v-model="form.target_entity" type="text" class="input" placeholder="Users" list="target-entities" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Direction</label>
        <select v-model="form.direction" class="input">
          <option value="inbound">Inbound (external → internal)</option>
          <option value="outbound">Outbound (internal → external)</option>
          <option value="bidirectional">Bidirectional</option>
        </select>
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
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="2" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Sample payload JSON</label>
        <textarea v-model="samplePayloadText" rows="4" class="input font-mono text-xs" placeholder='{"customer_name":"Ada Lovelace","weight":"62.5"}' />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">External schema fields (comma-separated)</label>
        <input v-model="externalSchemaText" type="text" class="input" placeholder="customer_name, weight, email" />
      </div>
    </div>

    <MappingBuilder
      v-model="fields"
      :transforms="transforms"
      :internal-fields="internalFields"
      :external-schema="externalSchemaOptions"
    />

    <datalist id="target-entities">
      <option v-for="entity in internalFields" :key="entity.entity" :value="entity.entity" />
    </datalist>

    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('cancel')">Cancel</button>
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="loading">
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { integrationService } from '@/modules/integrations/services/integrationService';
import MappingBuilder from '@/modules/mappings/components/MappingBuilder.vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save mapping' },
  hideCompany: { type: Boolean, default: false },
  catalogs: { type: Object, default: null },
});
const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const integrations = ref([]);
const form = reactive(createForm(props.initial));
const fields = ref([]);
const samplePayloadText = ref('');
const externalSchemaText = ref('');
const formError = ref('');

const transforms = computed(() => props.catalogs?.transforms || []);
const internalFields = computed(() => props.catalogs?.internal_fields || []);
const externalSchemaOptions = computed(() =>
  externalSchemaText.value
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
    .map((name) => ({ name })),
);

watch(() => props.initial, (value) => {
  Object.assign(form, createForm(value));
  fields.value = (value.fields || []).map((field, index) => ({
    external_field: field.external_field || '',
    internal_field: field.internal_field || '',
    transform_type: field.transform_type || 'none',
    transform_config_json: field.transform_config ? JSON.stringify(field.transform_config) : '',
    custom_rules_json: field.custom_rules?.length ? JSON.stringify(field.custom_rules) : '',
    is_required: Boolean(field.is_required),
    default_value: field.default_value ?? '',
    sort_order: field.sort_order ?? index,
    is_enabled: field.is_enabled !== false,
  }));
  if (!fields.value.length) {
    fields.value = [blankField(0), blankField(1)];
    fields.value[0].external_field = 'customer_name';
    fields.value[0].internal_field = 'Users.first_name';
    fields.value[0].transform_type = 'split_first';
    fields.value[0].transform_config_json = '{"delimiter":" "}';
    fields.value[0].is_required = true;
    fields.value[1].external_field = 'weight';
    fields.value[1].internal_field = 'Health.weight';
    fields.value[1].transform_type = 'cast_float';
    fields.value[1].is_required = true;
  }
  samplePayloadText.value = value.sample_payload ? JSON.stringify(value.sample_payload, null, 2) : '{"customer_name":"Ada Lovelace","weight":"62.5"}';
  const schema = value.external_schema || [];
  externalSchemaText.value = schema.map((item) => item.name || item.path || item).filter(Boolean).join(', ');
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
    source_entity: value.source_entity || 'EasyCarbs',
    target_entity: value.target_entity || 'Users',
    direction: value.direction || 'inbound',
    status: value.status || 'draft',
    is_active: value.is_active ?? true,
  };
}

function blankField(sortOrder = 0) {
  return {
    external_field: '',
    internal_field: '',
    transform_type: 'none',
    transform_config_json: '',
    custom_rules_json: '',
    is_required: false,
    default_value: '',
    sort_order: sortOrder,
    is_enabled: true,
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

function parseJsonField(text, label) {
  const trimmed = (text || '').trim();
  if (!trimmed) return {};
  try {
    return JSON.parse(trimmed);
  } catch {
    throw new Error(`${label} must be valid JSON.`);
  }
}

function onSubmit() {
  formError.value = '';
  try {
    const sample_payload = samplePayloadText.value.trim() ? parseJsonField(samplePayloadText.value, 'Sample payload') : null;
    const mappedFields = fields.value.map((row, index) => {
      const customRules = row.custom_rules_json?.trim()
        ? parseJsonField(row.custom_rules_json, `Custom rules for ${row.external_field || 'field'}`)
        : [];
      return {
        external_field: row.external_field,
        internal_field: row.internal_field,
        transform_type: row.transform_type || 'none',
        transform_config: row.transform_config_json?.trim()
          ? parseJsonField(row.transform_config_json, `Transform config for ${row.external_field || 'field'}`)
          : {},
        custom_rules: Array.isArray(customRules) ? customRules : [],
        is_required: Boolean(row.is_required),
        default_value: row.default_value === '' ? null : row.default_value,
        sort_order: index,
        is_enabled: row.is_enabled !== false,
      };
    });

    emit('submit', {
      ...form,
      sample_payload,
      external_schema: externalSchemaOptions.value,
      fields: mappedFields,
    });
  } catch (err) {
    formError.value = err.message || 'Invalid mapping form data.';
  }
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
