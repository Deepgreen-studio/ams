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
    <div v-if="showCategory">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
      <select v-model="local.category" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">All categories</option>
        <option v-for="category in categories" :key="category.value" :value="category.value">
          {{ category.label }}
        </option>
      </select>
    </div>
    <div v-if="showSearch">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
      <input
        v-model="local.search"
        type="search"
        placeholder="Search…"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
      />
    </div>
    <button
      type="button"
      class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      @click="emit('apply', { ...local })"
    >
      Apply filters
    </button>
    <button
      type="button"
      class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
      @click="onReset"
    >
      Reset
    </button>
    <button
      v-if="showSaveView"
      type="button"
      class="ml-auto rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-100"
      @click="emit('save-view', { ...local })"
    >
      Save view
    </button>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  showCategory: { type: Boolean, default: true },
  showSearch: { type: Boolean, default: false },
  showSaveView: { type: Boolean, default: false },
});

const emit = defineEmits(['apply', 'reset', 'save-view', 'update:modelValue']);

const local = reactive({
  from: props.modelValue.from || '',
  to: props.modelValue.to || '',
  category: props.modelValue.category || '',
  search: props.modelValue.search || '',
});

watch(
  () => props.modelValue,
  (value) => {
    local.from = value.from || '';
    local.to = value.to || '';
    local.category = value.category || '';
    local.search = value.search || '';
  },
  { deep: true }
);

function onReset() {
  const to = new Date();
  const from = new Date();
  from.setDate(to.getDate() - 29);
  local.from = from.toISOString().slice(0, 10);
  local.to = to.toISOString().slice(0, 10);
  local.category = '';
  local.search = '';
  emit('reset', { ...local });
}
</script>
