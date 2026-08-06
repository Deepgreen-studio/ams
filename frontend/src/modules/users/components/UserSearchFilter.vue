<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
      <div class="xl:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="local.search"
          type="search"
          placeholder="Name, email, phone..."
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
          @keyup.enter="emitSubmit"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Status</label
        >
        <select
          v-model="local.status"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
        >
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
          <option value="pending">Pending</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Created from</label
        >
        <input
          v-model="local.created_from"
          type="date"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Created to</label
        >
        <input
          v-model="local.created_to"
          type="date"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
        />
      </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
      <button
        type="button"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="emitSubmit"
      >
        Apply filters
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="emitReset"
      >
        Reset
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue', 'submit', 'reset']);

const local = reactive({
  search: props.modelValue.search || '',
  status: props.modelValue.status || '',
  created_from: props.modelValue.created_from || '',
  created_to: props.modelValue.created_to || '',
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.status = value.status || '';
    local.created_from = value.created_from || '';
    local.created_to = value.created_to || '';
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
  local.created_from = '';
  local.created_to = '';
  emit('reset');
}
</script>
