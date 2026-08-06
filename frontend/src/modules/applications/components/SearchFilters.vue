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
        placeholder="Name, slug, version..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
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
        <option value="draft">Draft</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="archived">Archived</option>
      </select>
    </div>
    <div class="w-full lg:w-36">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Platform</label
      >
      <select
        v-model="local.platform"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option value="android">Android</option>
        <option value="ios">iOS</option>
        <option value="web">Web</option>
        <option value="desktop">Desktop</option>
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
        <option value="business">Business</option>
        <option value="productivity">Productivity</option>
        <option value="utilities">Utilities</option>
        <option value="social">Social</option>
        <option value="education">Education</option>
        <option value="health">Health</option>
        <option value="finance">Finance</option>
        <option value="entertainment">Entertainment</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="w-full lg:w-36">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Visibility</label
      >
      <select
        v-model="local.visibility"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option value="private">Private</option>
        <option value="internal">Internal</option>
        <option value="public">Public</option>
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
});
const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const local = reactive({
  search: '',
  status: '',
  platform: '',
  category: '',
  visibility: '',
  trashed: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.status = value.status || '';
    local.platform = value.platform || '';
    local.category = value.category || '';
    local.visibility = value.visibility || '';
    local.trashed = value.trashed || '';
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  const payload = {
    search: local.search,
    status: local.status,
    platform: local.platform,
    category: local.category,
    visibility: local.visibility,
    trashed: local.trashed,
    page: 1,
  };
  emit('update:modelValue', { ...props.modelValue, ...payload });
  emit('submit', payload);
}

function onReset() {
  local.search = '';
  local.status = '';
  local.platform = '';
  local.category = '';
  local.visibility = '';
  local.trashed = '';
  emit('reset');
}
</script>
