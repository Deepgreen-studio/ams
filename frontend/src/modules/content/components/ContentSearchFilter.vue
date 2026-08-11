<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="relative min-w-0 flex-1 lg:max-w-sm">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        v-model="local.search"
        type="search"
        placeholder="Title, slug, excerpt..."
        class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        @keyup.enter="onSubmit"
      />
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <SelectBox
        v-model="local.type"
        wrapper-class="min-w-[9.5rem]"
        :options="typeOptions"
        @change="onSubmit"
      />
      <SelectBox
        v-model="local.status"
        wrapper-class="min-w-[9.5rem]"
        :options="statusOptions"
        @change="onSubmit"
      />
      <SelectBox
        v-model="local.category"
        wrapper-class="min-w-[10rem]"
        :options="categoryOptions"
        @change="onSubmit"
      />
      <SelectBox
        v-model="local.trashed"
        wrapper-class="min-w-[10rem]"
        :options="trashedOptions"
        @change="onSubmit"
      />

      <button
        type="button"
        class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
        @click="onSubmit"
      >
        Apply
      </button>
      <button
        type="button"
        class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="onReset"
      >
        Reset
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';

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

const typeOptions = computed(() => [
  { value: '', label: 'Type: All' },
  ...props.types.map((type) => ({ value: type.slug, label: type.name })),
]);

const statusOptions = computed(() => [
  { value: '', label: 'Status: All' },
  ...props.statuses.map((status) => ({ value: status.slug, label: status.name })),
]);

const categoryOptions = computed(() => [
  { value: '', label: 'Category: All' },
  ...props.categories.map((category) => ({ value: category.slug, label: category.name })),
]);

const trashedOptions = [
  { value: '', label: 'Deleted: Exclude' },
  { value: 'with', label: 'Include deleted' },
  { value: 'only', label: 'Only deleted' },
];

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
