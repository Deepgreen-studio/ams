<template>
  <form
    class="rounded-[12px] bg-white p-4 ring-1 ring-zinc-100 sm:p-5"
    @submit.prevent="onSubmit"
  >
    <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
      <div class="relative min-w-0 flex-1 lg:max-w-xs">
        <MagnifyingGlassIcon
          class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
        />
        <input
          v-model="local.search"
          type="search"
          placeholder="Search…"
          class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <input
        v-if="showModule"
        v-model="local.module"
        type="text"
        placeholder="Module"
        class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0 lg:w-40"
      />

      <input
        v-if="showAction"
        v-model="local.action"
        type="text"
        placeholder="Action"
        class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0 lg:w-40"
      />

      <input
        v-model="local.date_from"
        type="date"
        title="From date"
        class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
      />

      <input
        v-model="local.date_to"
        type="date"
        title="To date"
        class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
      />

      <div class="flex flex-wrap items-center gap-2">
        <button
          type="submit"
          class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Filter
        </button>
        <button
          type="button"
          class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-brand-700 hover:bg-brand-50"
          @click="onReset"
        >
          Reset
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

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

function onSubmit() {
  const payload = { ...local, page: 1 };
  emit('update:modelValue', { ...props.modelValue, ...payload });
  emit('submit', payload);
}

function onReset() {
  local.search = '';
  local.module = '';
  local.action = '';
  local.date_from = '';
  local.date_to = '';
  emit('reset');
}
</script>
