<template>
  <div
    class="mb-4 flex flex-col gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 sm:px-8 lg:flex-row lg:items-end lg:justify-between"
  >
    <form class="flex flex-wrap items-end gap-3" @submit.prevent="onApply">
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">From</label>
        <input v-model="local.from" type="date" class="input min-w-[10.5rem]" />
      </div>
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">To</label>
        <input v-model="local.to" type="date" class="input min-w-[10.5rem]" />
      </div>
      <div v-if="showCategory" class="min-w-[12rem]">
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
        <SelectBox v-model="local.category" size="lg" :options="categorySelectOptions" />
      </div>
      <div v-if="showSearch" class="min-w-[14rem] flex-1">
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
        <div class="relative">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            v-model="local.search"
            type="search"
            placeholder="Search…"
            class="input pl-10"
          />
        </div>
      </div>
      <slot />
      <button
        type="submit"
        class="inline-flex h-12 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Apply filters
      </button>
      <button
        type="button"
        class="inline-flex h-12 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="onReset"
      >
        Reset
      </button>
    </form>
    <button
      v-if="showSaveView"
      type="button"
      class="inline-flex h-12 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      @click="emit('save-view', { ...local })"
    >
      Save view
    </button>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  showCategory: { type: Boolean, default: true },
  showSearch: { type: Boolean, default: false },
  showSaveView: { type: Boolean, default: false },
});

const emit = defineEmits(['apply', 'reset', 'save-view', 'update:modelValue']);

const local = reactive({
  from: props.modelValue.from || '',
  to: props.modelValue.to || '',
  category: props.modelValue.category || '',
  search: props.modelValue.search || '',
});

const categorySelectOptions = computed(() => [
  { value: '', label: 'All categories' },
  ...props.categories.map((category) => ({
    value: category.value,
    label: category.label,
  })),
]);

watch(
  () => props.modelValue,
  (value) => {
    local.from = value.from || '';
    local.to = value.to || '';
    local.category = value.category || '';
    local.search = value.search || '';
  },
  { deep: true },
);

function onApply() {
  const payload = { ...local };
  emit('update:modelValue', payload);
  emit('apply', payload);
}

function onReset() {
  const to = new Date();
  const from = new Date();
  from.setDate(to.getDate() - 29);
  local.from = from.toISOString().slice(0, 10);
  local.to = to.toISOString().slice(0, 10);
  local.category = '';
  local.search = '';
  const payload = { ...local };
  emit('update:modelValue', payload);
  emit('reset', payload);
}
</script>
