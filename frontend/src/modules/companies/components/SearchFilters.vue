<template>
  <form
    class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:items-end"
    @submit.prevent="onSubmit"
  >
    <div class="flex-1">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Search</label
      >
      <input
        v-model="local.search"
        type="search"
        placeholder="Name, email, registration..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
    <div class="w-full md:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Status</label
      >
      <select
        v-model="local.status"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="suspended">Suspended</option>
        <option value="pending">Pending</option>
      </select>
    </div>
    <div class="w-full md:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Deleted</label
      >
      <select
        v-model="local.trashed"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">Exclude</option>
        <option value="with">Include</option>
        <option value="only">Only deleted</option>
      </select>
    </div>
    <div class="flex gap-2">
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
});
const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const local = reactive({
  search: '',
  status: '',
  trashed: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.status = value.status || '';
    local.trashed = value.trashed || '';
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  const payload = { search: local.search, status: local.status, trashed: local.trashed, page: 1 };
  emit('update:modelValue', { ...props.modelValue, ...payload });
  emit('submit', payload);
}

function onReset() {
  local.search = '';
  local.status = '';
  local.trashed = '';
  emit('reset');
}
</script>
