<template>
  <form
    class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:flex-row lg:flex-wrap lg:items-end"
    @submit.prevent="onSubmit"
  >
    <div class="min-w-[12rem] flex-1">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
      <input
        v-model="local.search"
        type="search"
        placeholder="Title, case number..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
      <select
        v-model="local.status"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-44">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
      <select
        v-model="local.case_type"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option v-for="option in typeOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-36">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Priority</label>
      <select
        v-model="local.priority"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">All</option>
        <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-36">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Overdue</label>
      <select
        v-model="local.overdue"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      >
        <option value="">Any</option>
        <option value="1">Overdue only</option>
      </select>
    </div>
    <div class="flex gap-2">
      <button
        type="submit"
        class="h-12 rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700"
      >
        Filter
      </button>
      <button
        type="button"
        class="h-12 rounded-[12px] border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="onReset"
      >
        Reset
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'reset']);

const statusOptions = [
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'under_review', label: 'Under Review' },
  { value: 'pending', label: 'Pending' },
  { value: 'completed', label: 'Completed' },
  { value: 'closed', label: 'Closed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const typeOptions = [
  { value: 'gdpr', label: 'GDPR' },
  { value: 'uk_gdpr', label: 'UK GDPR' },
  { value: 'privacy_request', label: 'Privacy Request' },
  { value: 'compliance_case', label: 'Compliance Case' },
  { value: 'risk_register', label: 'Risk Register' },
  { value: 'audit_compliance', label: 'Audit Compliance' },
  { value: 'iso_27001', label: 'ISO 27001' },
  { value: 'soc2', label: 'SOC 2' },
  { value: 'other', label: 'Other' },
];

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
];

const local = reactive({
  search: '',
  status: '',
  case_type: '',
  priority: '',
  overdue: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      status: value.status || '',
      case_type: value.case_type || '',
      priority: value.priority || '',
      overdue: value.overdue || '',
      page: 1,
    });
  },
  { immediate: true, deep: true }
);

function onSubmit() {
  emit('submit', { ...local, page: 1 });
}

function onReset() {
  Object.assign(local, {
    search: '',
    status: '',
    case_type: '',
    priority: '',
    overdue: '',
    page: 1,
  });
  emit('reset');
}
</script>
