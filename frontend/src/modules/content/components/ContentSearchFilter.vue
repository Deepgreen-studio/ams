<template>
  <form
    class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:flex-row lg:flex-wrap lg:items-end"
    @submit.prevent="onSubmit"
  >
    <div class="min-w-[12rem] flex-1">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Search</label
      >
      <input
        v-model="local.search"
        type="search"
        placeholder="Title, slug, excerpt..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Type</label
      >
      <select
        v-model="local.type"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option v-for="type in types" :key="type.uuid" :value="type.slug">{{ type.name }}</option>
      </select>
    </div>
    <div class="w-full lg:w-36">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Status</label
      >
      <select
        v-model="local.status"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option v-for="status in statuses" :key="status.uuid" :value="status.slug">
          {{ status.name }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Category</label
      >
      <select
        v-model="local.category"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option v-for="category in categories" :key="category.uuid" :value="category.slug">
          {{ category.name }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-36">
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
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const local = reactive({
  search: '',
  type: '',
  status: '',
  category: '',
  trashed: '',
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.type = value.type || '';
    local.status = value.status || '';
    local.category = value.category || '';
    local.trashed = value.trashed || '';
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  const payload = {
    search: local.search,
    type: local.type,
    status: local.status,
    category: local.category,
    trashed: local.trashed,
    page: 1,
  };
  emit('update:modelValue', { ...props.modelValue, ...payload });
  emit('submit', payload);
}

function onReset() {
  local.search = '';
  local.type = '';
  local.status = '';
  local.category = '';
  local.trashed = '';
  emit('reset');
}
</script>
