<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Visual Mapping Builder</h3>
        <p class="text-xs text-slate-500">Map external fields to internal AMS fields with transforms and rules.</p>
      </div>
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="addRow">
        Add field
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-3 py-2 text-left font-semibold text-slate-600">External field</th>
            <th class="px-3 py-2 text-center font-semibold text-slate-400">→</th>
            <th class="px-3 py-2 text-left font-semibold text-slate-600">Internal field</th>
            <th class="px-3 py-2 text-left font-semibold text-slate-600">Transform</th>
            <th class="px-3 py-2 text-left font-semibold text-slate-600">Required / Default</th>
            <th class="px-3 py-2 text-right font-semibold text-slate-600" />
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr v-for="(row, index) in model" :key="index" class="align-top">
            <td class="px-3 py-3">
              <input v-model="row.external_field" type="text" class="input" list="external-fields" placeholder="customer_name" />
            </td>
            <td class="px-2 py-3 text-center text-slate-400">↓</td>
            <td class="px-3 py-3">
              <input v-model="row.internal_field" type="text" class="input" list="internal-fields" placeholder="Users.first_name" />
            </td>
            <td class="px-3 py-3 space-y-2">
              <select v-model="row.transform_type" class="input">
                <option v-for="item in transforms" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
              <input
                v-if="needsConfig(row.transform_type)"
                v-model="row.transform_config_json"
                type="text"
                class="input font-mono text-xs"
                :placeholder="configPlaceholder(row.transform_type)"
              />
              <textarea
                v-model="row.custom_rules_json"
                rows="2"
                class="input font-mono text-xs"
                placeholder='Custom rules JSON [{"type":"min","value":1}]'
              />
            </td>
            <td class="px-3 py-3 space-y-2">
              <label class="flex items-center gap-2 text-xs text-slate-700">
                <input v-model="row.is_required" type="checkbox" class="rounded border-slate-300" />
                Required
              </label>
              <input v-model="row.default_value" type="text" class="input" placeholder="Default value" />
            </td>
            <td class="px-3 py-3 text-right">
              <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50" @click="removeRow(index)">
                Remove
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <datalist id="external-fields">
      <option v-for="field in externalSuggestions" :key="field" :value="field" />
    </datalist>
    <datalist id="internal-fields">
      <option v-for="field in internalSuggestions" :key="field" :value="field" />
    </datalist>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const model = defineModel({ type: Array, required: true });

const props = defineProps({
  transforms: { type: Array, default: () => [] },
  internalFields: { type: Array, default: () => [] },
  externalSchema: { type: Array, default: () => [] },
});

const internalSuggestions = computed(() => {
  const paths = [];
  for (const entity of props.internalFields) {
    for (const field of entity.fields || []) {
      paths.push(field.path);
    }
  }
  return paths;
});

const externalSuggestions = computed(() => props.externalSchema.map((item) => item.name || item.path || item).filter(Boolean));

function addRow() {
  model.value = [
    ...model.value,
    {
      external_field: '',
      internal_field: '',
      transform_type: 'none',
      transform_config_json: '',
      custom_rules_json: '',
      is_required: false,
      default_value: '',
      sort_order: model.value.length,
      is_enabled: true,
    },
  ];
}

function removeRow(index) {
  model.value = model.value.filter((_, i) => i !== index);
}

function needsConfig(type) {
  return ['date_format', 'replace', 'prefix', 'suffix', 'split_first', 'split_last', 'lookup', 'template'].includes(type);
}

function configPlaceholder(type) {
  const map = {
    date_format: '{"format":"Y-m-d"}',
    replace: '{"search":"x","replace":"y"}',
    prefix: '{"value":"PRE-"}',
    suffix: '{"value":"-SFX"}',
    split_first: '{"delimiter":" "}',
    split_last: '{"delimiter":" "}',
    lookup: '{"map":{"a":"b"}}',
    template: '{"template":"Hello {value}"}',
  };
  return map[type] || '{}';
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
