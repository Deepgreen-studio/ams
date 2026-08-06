<template>
  <form
    class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-6"
    @submit.prevent="$emit('submit', local)"
  >
    <input
      v-model="local.search"
      type="search"
      placeholder="Search..."
      class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm md:col-span-2"
    />
    <input
      v-if="showModule"
      v-model="local.module"
      type="text"
      placeholder="Module"
      class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
    />
    <input
      v-if="showAction"
      v-model="local.action"
      type="text"
      placeholder="Action"
      class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
    />
    <input
      v-model="local.date_from"
      type="date"
      class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
    />
    <input
      v-model="local.date_to"
      type="date"
      class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
    />
    <div class="flex gap-2 md:col-span-6">
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Filter
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="onReset"
      >
        Reset
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  showModule: { type: Boolean, default: true },
  showAction: { type: Boolean, default: true },
});
const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const local = reactive({
  search: '',
  module: '',
  action: '',
  date_from: '',
  date_to: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      module: value.module || '',
      action: value.action || '',
      date_from: value.date_from || '',
      date_to: value.date_to || '',
    });
  },
  { immediate: true, deep: true },
);

function onReset() {
  local.search = '';
  local.module = '';
  local.action = '';
  local.date_from = '';
  local.date_to = '';
  emit('reset');
}
</script>
