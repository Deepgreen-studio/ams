<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="relative min-w-0 flex-1 lg:max-w-sm">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        v-model="local.search"
        type="search"
        placeholder="Name, slug, URL..."
        class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        @keyup.enter="emitSubmit"
      />
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <SelectBox
        v-model="local.status"
        wrapper-class="min-w-[9.5rem]"
        :options="statusOptions"
        @change="emitSubmit"
      />

      <SelectBox
        v-model="local.type"
        wrapper-class="min-w-[9.5rem]"
        :options="typeOptions"
        @change="emitSubmit"
      />

      <SelectBox
        v-model="local.authentication_type"
        wrapper-class="min-w-[10.5rem]"
        :options="authOptions"
        @change="emitSubmit"
      />

      <SelectBox
        v-model="local.trashed"
        wrapper-class="min-w-[10rem]"
        :options="trashedOptions"
        @change="emitSubmit"
      />

      <button
        type="button"
        class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
        @click="emitSubmit"
      >
        Apply
      </button>
      <button
        type="button"
        class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="emitReset"
      >
        Reset
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue', 'submit', 'reset']);

const statusOptions = [
  { value: '', label: 'Status: All' },
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'error', label: 'Error' },
];

const typeOptions = [
  { value: '', label: 'Type: All' },
  { value: 'rest_api', label: 'REST API' },
  { value: 'graphql', label: 'GraphQL' },
  { value: 'webhook', label: 'Webhook' },
  { value: 'sdk', label: 'SDK' },
  { value: 'ftp', label: 'FTP' },
  { value: 'database', label: 'Database' },
];

const authOptions = [
  { value: '', label: 'Auth: All' },
  { value: 'api_key', label: 'API Key' },
  { value: 'bearer_token', label: 'Bearer Token' },
  { value: 'basic_auth', label: 'Basic Auth' },
  { value: 'jwt', label: 'JWT' },
  { value: 'oauth2', label: 'OAuth2' },
];

const trashedOptions = [
  { value: '', label: 'Deleted: Exclude' },
  { value: 'with', label: 'Include deleted' },
  { value: 'only', label: 'Only deleted' },
];

const local = reactive({
  search: props.modelValue.search || '',
  status: props.modelValue.status || '',
  type: props.modelValue.type || '',
  authentication_type: props.modelValue.authentication_type || '',
  trashed: props.modelValue.trashed || '',
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
  { deep: true },
);

function emitSubmit() {
  emit('update:modelValue', { ...props.modelValue, ...local, page: 1 });
  emit('submit', { ...local, page: 1 });
}

function emitReset() {
  local.search = '';
  local.status = '';
  local.type = '';
  local.authentication_type = '';
  local.trashed = '';
  emit('reset');
}
</script>
