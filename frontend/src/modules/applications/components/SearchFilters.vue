<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="relative min-w-0 flex-1 lg:max-w-sm">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        v-model="local.search"
        type="search"
        placeholder="Name, slug, version..."
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
        v-model="local.platform"
        wrapper-class="min-w-[9.5rem]"
        :options="platformOptions"
        @change="emitSubmit"
      />

      <SelectBox
        v-model="local.category"
        wrapper-class="min-w-[10.5rem]"
        :options="categoryOptions"
        @change="emitSubmit"
      />

      <SelectBox
        v-model="local.visibility"
        wrapper-class="min-w-[10rem]"
        :options="visibilityOptions"
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
  { value: 'archived', label: 'Archived' },
];

const platformOptions = [
  { value: '', label: 'Platform: All' },
  { value: 'android', label: 'Android' },
  { value: 'ios', label: 'iOS' },
  { value: 'web', label: 'Web' },
  { value: 'desktop', label: 'Desktop' },
];

const categoryOptions = [
  { value: '', label: 'Category: All' },
  { value: 'business', label: 'Business' },
  { value: 'productivity', label: 'Productivity' },
  { value: 'utilities', label: 'Utilities' },
  { value: 'social', label: 'Social' },
  { value: 'education', label: 'Education' },
  { value: 'health', label: 'Health' },
  { value: 'finance', label: 'Finance' },
  { value: 'entertainment', label: 'Entertainment' },
  { value: 'other', label: 'Other' },
];

const visibilityOptions = [
  { value: '', label: 'Visibility: All' },
  { value: 'private', label: 'Private' },
  { value: 'internal', label: 'Internal' },
  { value: 'public', label: 'Public' },
];

const trashedOptions = [
  { value: '', label: 'Deleted: Exclude' },
  { value: 'with', label: 'Include deleted' },
  { value: 'only', label: 'Only deleted' },
];

const local = reactive({
  search: props.modelValue.search || '',
  status: props.modelValue.status || '',
  platform: props.modelValue.platform || '',
  category: props.modelValue.category || '',
  visibility: props.modelValue.visibility || '',
  trashed: props.modelValue.trashed || '',
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
  { deep: true },
);

function emitSubmit() {
  emit('update:modelValue', { ...props.modelValue, ...local, page: 1 });
  emit('submit', { ...local, page: 1 });
}

function emitReset() {
  local.search = '';
  local.status = '';
  local.platform = '';
  local.category = '';
  local.visibility = '';
  local.trashed = '';
  emit('reset');
}
</script>
