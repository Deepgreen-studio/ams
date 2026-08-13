<template>
  <div
    class="mb-4 flex flex-col gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 sm:px-8 lg:flex-row lg:items-end lg:justify-between"
  >
    <form class="flex flex-wrap items-end gap-3" @submit.prevent="onApply">
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">From</label>
        <input v-model="local.from" type="date" class="input" />
      </div>
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">To</label>
        <input v-model="local.to" type="date" class="input" />
      </div>
      <button
        type="submit"
        class="inline-flex h-11 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Apply
      </button>
      <button
        type="button"
        class="inline-flex h-11 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="onReset"
      >
        Reset
      </button>
    </form>
    <div class="flex flex-wrap gap-2">
      <button
        type="button"
        class="inline-flex h-11 items-center gap-2 rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="exporting"
        @click="emit('export', 'csv')"
      >
        <ArrowDownTrayIcon class="h-4 w-4" />
        Export CSV
      </button>
      <button
        type="button"
        class="inline-flex h-11 items-center gap-2 rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="exporting"
        @click="emit('export', 'excel')"
      >
        <ArrowDownTrayIcon class="h-4 w-4" />
        Export Excel
      </button>
      <button
        type="button"
        class="inline-flex h-11 items-center gap-2 rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="exporting"
        @click="emit('export', 'pdf')"
      >
        <DocumentArrowDownIcon class="h-4 w-4" />
        PDF Ready
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { ArrowDownTrayIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: { type: Object, required: true },
  exporting: { type: Boolean, default: false },
});

const emit = defineEmits(['apply', 'export', 'reset', 'update:modelValue']);

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
  { deep: true },
);

function onApply() {
  const payload = { ...local };
  emit('update:modelValue', payload);
  emit('apply', payload);
}

function onReset() {
  emit('reset');
}
</script>
