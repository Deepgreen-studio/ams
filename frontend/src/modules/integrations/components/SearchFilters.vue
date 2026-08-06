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
        placeholder="Name, slug, URL..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
    <div class="w-full lg:w-40">
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
        <option value="error">Error</option>
      </select>
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
        <option value="rest_api">REST API</option>
        <option value="graphql">GraphQL</option>
        <option value="webhook">Webhook</option>
        <option value="sdk">SDK</option>
        <option value="ftp">FTP</option>
        <option value="database">Database</option>
      </select>
    </div>
    <div class="w-full lg:w-44">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Auth</label
      >
      <select
        v-model="local.authentication_type"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option value="api_key">API Key</option>
        <option value="bearer_token">Bearer Token</option>
        <option value="basic_auth">Basic Auth</option>
        <option value="jwt">JWT</option>
        <option value="oauth2">OAuth2</option>
      </select>
    </div>
    <div class="w-full lg:w-40">
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
  type: '',
  authentication_type: '',
  trashed: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.status = value.status || '';
    local.type = value.type || '';
    local.authentication_type = value.authentication_type || '';
    local.trashed = value.trashed || '';
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  const payload = {
    search: local.search,
    status: local.status,
    type: local.type,
    authentication_type: local.authentication_type,
    trashed: local.trashed,
    page: 1,
  };
  emit('update:modelValue', { ...props.modelValue, ...payload });
  emit('submit', payload);
}

function onReset() {
  local.search = '';
  local.status = '';
  local.type = '';
  local.authentication_type = '';
  local.trashed = '';
  emit('reset');
}
</script>
