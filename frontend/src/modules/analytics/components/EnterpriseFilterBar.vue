<template>
  <div
    :class="
      embedded
        ? ''
        : 'mb-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 sm:px-8'
    "
  >
    <form
      class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
      @submit.prevent="onApply"
    >
      <div v-if="showSearch" class="relative min-w-0 w-full flex-1 lg:max-w-sm">
        <div class="relative">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            v-model="local.search"
            type="search"
            placeholder="Search event, source…"
            :class="compactSearchClass"
          />
        </div>
      </div>

      <div
        v-else-if="showPresets"
        class="flex min-w-0 flex-wrap items-center gap-1.5"
      >
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
        <SelectBox
          v-if="showCategory"
          v-model="local.category"
          wrapper-class="min-w-[11rem]"
          :options="categorySelectOptions"
        />
        <slot />
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
        <button
          v-if="showSaveView"
          type="button"
          :class="compactButtonClass"
          class="border border-zinc-200 text-slate-700 hover:bg-zinc-50"
          @click="emit('save-view', { ...local })"
        >
          Save view
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  showCategory: { type: Boolean, default: true },
  showSearch: { type: Boolean, default: false },
  showSaveView: { type: Boolean, default: false },
  showPresets: { type: Boolean, default: true },
  embedded: { type: Boolean, default: false },
});

const compactSearchClass =
  'h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0';
const compactInputClass =
  'h-10 min-w-[10.5rem] rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0';
const compactButtonClass = 'inline-flex h-10 items-center rounded-[12px] px-5 text-sm font-medium';

const emit = defineEmits(['apply', 'reset', 'save-view', 'update:modelValue']);

const rangePresets = [
  { id: '7d', label: '7 days', days: 7 },
  { id: '30d', label: '30 days', days: 30 },
  { id: '90d', label: '90 days', days: 90 },
];

const local = reactive({
  from: props.modelValue.from || '',
  to: props.modelValue.to || '',
  category: props.modelValue.category || '',
  search: props.modelValue.search || '',
});

const categorySelectOptions = computed(() => [
  { value: '', label: 'All categories' },
  ...props.categories.map((category) => ({
    value: category.value,
    label: category.label,
  })),
]);

watch(
  () => props.modelValue,
  (value) => {
    local.from = value.from || '';
    local.to = value.to || '';
    local.category = value.category || '';
    local.search = value.search || '';
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
  local.category = '';
  local.search = '';
  const payload = { ...local };
  emit('update:modelValue', payload);
  emit('reset', payload);
}
</script>
