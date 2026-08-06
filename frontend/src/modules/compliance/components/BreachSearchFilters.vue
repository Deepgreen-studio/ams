<template>
  <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
    <form class="grid gap-3 lg:grid-cols-5" @submit.prevent="$emit('search', model)">
      <div class="lg:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
        <input
          v-model="model.search"
          type="search"
          placeholder="Number, title..."
          class="h-11 w-full rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
        <select v-model="model.status" class="h-11 w-full rounded-[12px] border border-slate-300 px-3 text-sm">
          <option value="">All</option>
          <option value="reported">Reported</option>
          <option value="assessing">Assessing</option>
          <option value="contained">Contained</option>
          <option value="recovering">Recovering</option>
          <option value="notifying">Notifying</option>
          <option value="closed">Closed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Severity</label>
        <select v-model="model.severity" class="h-11 w-full rounded-[12px] border border-slate-300 px-3 text-sm">
          <option value="">All</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button type="submit" class="h-11 flex-1 rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700">
          Search
        </button>
        <button
          type="button"
          class="h-11 rounded-[12px] border border-slate-300 px-3 text-sm text-slate-700 hover:bg-slate-50"
          @click="onReset"
        >
          Reset
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['search', 'reset']);

const model = reactive({
  search: '',
  status: '',
  severity: '',
  breach_type: '',
  page: 1,
});

watch(
  () => props.filters,
  (value) => {
    Object.assign(model, {
      search: value.search || '',
      status: value.status || '',
      severity: value.severity || '',
      breach_type: value.breach_type || '',
      page: value.page || 1,
    });
  },
  { immediate: true, deep: true }
);

function onReset() {
  Object.assign(model, { search: '', status: '', severity: '', breach_type: '', page: 1 });
  emit('reset', { ...model });
}
</script>
