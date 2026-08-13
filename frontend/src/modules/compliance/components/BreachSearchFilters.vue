<template>
  <form
    class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
    @submit.prevent="onSubmit"
  >
    <div class="relative min-w-0 flex-1 lg:max-w-sm">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        v-model="local.search"
        type="search"
        placeholder="Search incident #, title…"
        class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
      />
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <SelectBox
        v-model="local.status"
        wrapper-class="min-w-[9.5rem]"
        :options="statusSelectOptions"
      />
      <SelectBox
        v-model="local.severity"
        wrapper-class="min-w-[9.5rem]"
        :options="severitySelectOptions"
      />
      <SelectBox
        v-model="local.breach_type"
        wrapper-class="min-w-[11.5rem]"
        :options="typeSelectOptions"
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
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import {
  breachSeverityOptions,
  breachStatusOptions,
  breachTypeOptions,
} from '@/modules/compliance/utils/breachOptions';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const local = reactive({
  search: '',
  status: '',
  severity: '',
  breach_type: '',
});

const statusSelectOptions = [{ value: '', label: 'All statuses' }, ...breachStatusOptions];
const severitySelectOptions = [{ value: '', label: 'All severities' }, ...breachSeverityOptions];
const typeSelectOptions = [{ value: '', label: 'All types' }, ...breachTypeOptions];

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      status: value.status || '',
      severity: value.severity || '',
      breach_type: value.breach_type || '',
    });
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  const payload = { ...local, page: 1 };
  emit('update:modelValue', payload);
  emit('submit', payload);
}

function onReset() {
  Object.assign(local, {
    search: '',
    status: '',
    severity: '',
    breach_type: '',
  });
  emit('reset');
}
</script>
