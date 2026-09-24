<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div class="relative min-w-0 flex-1 lg:max-w-sm">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        v-model="local.search"
        type="search"
        placeholder="Search name, email, phone..."
        class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        @input="onSearchInput"
        @search="onSearchInput"
      />
    </div>

    <div class="flex flex-wrap items-end gap-2">
      <SelectBox
        v-model="local.status"
        wrapper-class="min-w-[9.5rem]"
        :options="statusOptions"
        @change="emitSubmit"
      />

      <div>
        <label for="users-filter-start-date" class="mb-1 block text-xs font-medium text-slate-500">
          Start date
        </label>
        <input
          id="users-filter-start-date"
          v-model="local.created_from"
          type="date"
          class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          @change="emitSubmit"
        />
      </div>

      <div>
        <label for="users-filter-end-date" class="mb-1 block text-xs font-medium text-slate-500">
          End date
        </label>
        <input
          id="users-filter-end-date"
          v-model="local.created_to"
          type="date"
          class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          @change="emitSubmit"
        />
      </div>

      <button
        type="button"
        class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
        @click="emitSubmit"
      >
        Apply Filter
      </button>
      <button
        type="button"
        class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="emitReset"
      >
        Reset Filter
      </button>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, reactive, watch } from 'vue';
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
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'pending', label: 'Pending' },
];

const local = reactive({
  search: props.modelValue.search || '',
  status: props.modelValue.status || '',
  created_from: props.modelValue.created_from || '',
  created_to: props.modelValue.created_to || '',
});

let searchTimer = null;
let skipSearchSync = false;

watch(
  () => props.modelValue,
  (value) => {
    if (!skipSearchSync) {
      local.search = value.search || '';
    }

    skipSearchSync = false;
    local.status = value.status || '';
    local.created_from = value.created_from || '';
    local.created_to = value.created_to || '';
  },
  { deep: true },
);

function onSearchInput() {
  window.clearTimeout(searchTimer);
  const delay = String(local.search || '').trim() ? 300 : 0;
  searchTimer = window.setTimeout(emitSubmit, delay);
}

function emitSubmit() {
  window.clearTimeout(searchTimer);
  skipSearchSync = true;
  emit('update:modelValue', { ...props.modelValue, ...local, page: 1 });
  emit('submit', { ...local, page: 1 });
}

function emitReset() {
  window.clearTimeout(searchTimer);
  local.search = '';
  local.status = '';
  local.created_from = '';
  local.created_to = '';
  emit('reset');
}

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
});
</script>
