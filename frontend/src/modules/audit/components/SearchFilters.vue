<template>
  <form
    :class="
      embedded
        ? 'flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between'
        : 'rounded-[12px] bg-white p-4 ring-1 ring-zinc-100 sm:p-5'
    "
    @submit.prevent="onSubmit"
  >
    <div :class="embedded ? 'contents' : 'flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center'">
      <div class="relative min-w-0 flex-1 lg:max-w-xs">
        <MagnifyingGlassIcon
          class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
        />
        <input
          v-model="local.search"
          type="search"
          :placeholder="placeholder"
          class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <SelectBox
          v-if="showModule"
          v-model="local.module"
          wrapper-class="min-w-[9.5rem]"
          :options="moduleSelectOptions"
        />

        <SelectBox
          v-if="showAction"
          v-model="local.action"
          wrapper-class="min-w-[9.5rem]"
          :options="actionSelectOptions"
        />

        <SelectBox
          v-if="showStatus"
          v-model="local.status"
          wrapper-class="min-w-[9.5rem]"
          :options="statusSelectOptions"
        />

        <input
          v-model="local.date_from"
          type="date"
          title="From date"
          class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />

        <input
          v-model="local.date_to"
          type="date"
          title="To date"
          class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 py-2 text-sm text-slate-700 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />

        <button
          type="submit"
          class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
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
  </form>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { auditActionOptions, auditModuleOptions, loginStatusOptions } from '@/modules/audit/utils/auditOptions';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  showModule: { type: Boolean, default: true },
  showAction: { type: Boolean, default: true },
  showStatus: { type: Boolean, default: false },
  embedded: { type: Boolean, default: false },
  placeholder: { type: String, default: 'Search module, action, reason…' },
});
const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const local = reactive({
  search: '',
  module: '',
  action: '',
  status: '',
  date_from: '',
  date_to: '',
  page: 1,
});

const moduleSelectOptions = computed(() =>
  withCurrentOption([{ value: '', label: 'All modules' }, ...auditModuleOptions], local.module),
);

const actionSelectOptions = computed(() =>
  withCurrentOption([{ value: '', label: 'All actions' }, ...auditActionOptions], local.action),
);

const statusSelectOptions = computed(() => [
  { value: '', label: 'All statuses' },
  ...loginStatusOptions,
]);

function withCurrentOption(options, current) {
  if (!current || options.some((option) => option.value === current)) {
    return options;
  }

  return [
    ...options,
    {
      value: current,
      label: String(current)
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase()),
    },
  ];
}

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      module: value.module || '',
      action: value.action || '',
      status: value.status || '',
      date_from: value.date_from || '',
      date_to: value.date_to || '',
    });
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  const payload = { ...local, page: 1 };
  emit('update:modelValue', { ...props.modelValue, ...payload });
  emit('submit', payload);
}

function onReset() {
  local.search = '';
  local.module = '';
  local.action = '';
  local.status = '';
  local.date_from = '';
  local.date_to = '';
  emit('reset');
}
</script>
