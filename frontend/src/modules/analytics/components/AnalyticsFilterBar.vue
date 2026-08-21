<template>
  <div class="mb-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 sm:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <form
        class="flex min-w-0 flex-1 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
        @submit.prevent="onApply"
      >
        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
          <span class="mr-1 text-xs font-medium uppercase tracking-wide text-slate-500">Period</span>
          <button
            v-for="preset in rangePresets"
            :key="preset.id"
            type="button"
            class="rounded-[10px] px-3 py-1.5 text-xs font-medium transition"
            :class="
              isPresetActive(preset.days)
                ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100'
                : 'bg-white text-slate-600 ring-1 ring-zinc-200 hover:bg-zinc-50'
            "
            @click="applyPreset(preset.days)"
          >
            {{ preset.label }}
          </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <input
            v-model="local.from"
            type="date"
            title="From date"
            :class="compactInputClass"
          />
          <input
            v-model="local.to"
            type="date"
            title="To date"
            :class="compactInputClass"
          />
          <button
            type="submit"
            :class="compactButtonClass"
            class="bg-brand-600 text-white hover:bg-brand-700"
          >
            Apply
          </button>
          <button
            type="button"
            :class="compactButtonClass"
            class="border border-zinc-200 text-slate-700 hover:bg-zinc-50"
            @click="onReset"
          >
            Reset
          </button>
        </div>
      </form>

      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          :class="compactButtonClass"
          class="border border-zinc-200 text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="exporting"
          @click="emit('export', 'csv')"
        >
          <ArrowDownTrayIcon class="h-4 w-4" />
          CSV
        </button>
        <button
          type="button"
          :class="compactButtonClass"
          class="border border-zinc-200 text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="exporting"
          @click="emit('export', 'excel')"
        >
          <ArrowDownTrayIcon class="h-4 w-4" />
          Excel
        </button>
        <button
          type="button"
          :class="compactButtonClass"
          class="border border-zinc-200 text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="exporting"
          @click="emit('export', 'pdf')"
        >
          <DocumentArrowDownIcon class="h-4 w-4" />
          PDF
        </button>
      </div>
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

const compactInputClass =
  'h-10 min-w-[10.5rem] rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0';
const compactButtonClass = 'inline-flex h-10 items-center gap-2 rounded-[12px] px-5 text-sm font-medium';

const rangePresets = [
  { id: '7d', label: '7 days', days: 7 },
  { id: '30d', label: '30 days', days: 30 },
  { id: '90d', label: '90 days', days: 90 },
];

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

function rangeDays() {
  if (!local.from || !local.to) {
    return null;
  }

  const from = new Date(`${local.from}T00:00:00`);
  const to = new Date(`${local.to}T00:00:00`);
  if (Number.isNaN(from.getTime()) || Number.isNaN(to.getTime())) {
    return null;
  }

  return Math.round((to.getTime() - from.getTime()) / 86400000) + 1;
}

function isPresetActive(days) {
  return rangeDays() === days;
}

function applyPreset(days) {
  const to = new Date();
  const from = new Date();
  from.setDate(to.getDate() - (days - 1));
  local.from = from.toISOString().slice(0, 10);
  local.to = to.toISOString().slice(0, 10);
  onApply();
}

function onApply() {
  const payload = { ...local };
  emit('update:modelValue', payload);
  emit('apply', payload);
}

function onReset() {
  const to = new Date();
  const from = new Date();
  from.setDate(to.getDate() - 29);
  local.from = from.toISOString().slice(0, 10);
  local.to = to.toISOString().slice(0, 10);
  local.company = '';
  const payload = { ...local };
  emit('update:modelValue', payload);
  emit('reset', payload);
}
</script>
