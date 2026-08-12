<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="relative min-w-0 flex-1 lg:max-w-sm">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        v-model="local.search"
        type="search"
        placeholder="Webhook, event, status code..."
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
        v-model="local.direction"
        wrapper-class="min-w-[9.5rem]"
        :options="directionOptions"
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
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'success', label: 'Success' },
  { value: 'failed', label: 'Failed' },
  { value: 'retrying', label: 'Retrying' },
];

const directionOptions = [
  { value: '', label: 'Direction: All' },
  { value: 'outgoing', label: 'Outgoing' },
  { value: 'incoming', label: 'Incoming' },
];

const local = reactive({
  search: props.modelValue.search || '',
  status: props.modelValue.status || '',
  direction: props.modelValue.direction || '',
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.status = value.status || '';
    local.direction = value.direction || '';
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
  local.direction = '';
  emit('reset');
}
</script>
