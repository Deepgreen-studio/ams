<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="grid gap-3 md:grid-cols-3">
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="local.search"
          type="search"
          placeholder="Role name or description..."
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          @keyup.enter="emitSubmit"
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Type</label
        >
        <select
          v-model="local.is_system"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        >
          <option value="">All roles</option>
          <option value="1">System roles</option>
          <option value="0">Custom roles</option>
        </select>
      </div>
    </div>
    <div class="mt-4 flex gap-2">
      <button
        type="button"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="emitSubmit"
      >
        Apply
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="$emit('reset')"
      >
        Reset
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
});

const emit = defineEmits(['update:modelValue', 'submit', 'reset']);

const local = reactive({
  search: props.modelValue.search || '',
  is_system: props.modelValue.is_system ?? '',
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.is_system = value.is_system ?? '';
  },
  { deep: true },
);

function emitSubmit() {
  emit('update:modelValue', { ...props.modelValue, ...local, page: 1 });
  emit('submit', { ...local, page: 1 });
}
</script>
