<template>
  <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">From</label>
      <input v-model="local.from" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
    </div>
    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">To</label>
      <input v-model="local.to" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
    </div>
    <button
      type="button"
      class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      @click="emit('apply', { ...local })"
    >
      Apply
    </button>
    <div class="ml-auto flex flex-wrap gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
        :disabled="exporting"
        @click="emit('export', 'csv')"
      >
        Export CSV
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
        :disabled="exporting"
        @click="emit('export', 'excel')"
      >
        Export Excel
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
        :disabled="exporting"
        @click="emit('export', 'pdf')"
      >
        PDF Ready
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
  exporting: { type: Boolean, default: false },
});

const emit = defineEmits(['apply', 'export', 'update:modelValue']);

const local = reactive({
  from: props.modelValue.from || '',
  to: props.modelValue.to || '',
  company: props.modelValue.company || '',
});

watch(
  () => props.modelValue,
  (value) => {
    local.from = value.from || '';
    local.to = value.to || '';
    local.company = value.company || '';
  },
  { deep: true }
);
</script>
