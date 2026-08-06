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
        placeholder="Number, name, email..."
        class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
      <select v-model="local.status" class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm">
        <option value="">All</option>
        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-48">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
      <select v-model="local.request_type" class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm">
        <option value="">All</option>
        <option v-for="option in typeOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-44">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Identity</label>
      <select
        v-model="local.identity_verification_status"
        class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm"
      >
        <option value="">All</option>
        <option value="pending">Pending</option>
        <option value="verified">Verified</option>
        <option value="failed">Failed</option>
        <option value="not_required">Not required</option>
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="h-12 rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700">
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
  { value: 'submitted', label: 'Submitted' },
  { value: 'identity_pending', label: 'Identity Pending' },
  { value: 'under_review', label: 'Under Review' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const typeOptions = [
  { value: 'access_request', label: 'Access Request' },
  { value: 'data_export', label: 'Data Export' },
  { value: 'data_correction', label: 'Data Correction' },
  { value: 'data_deletion', label: 'Data Deletion' },
  { value: 'restrict_processing', label: 'Restrict Processing' },
  { value: 'right_to_object', label: 'Right to Object' },
  { value: 'consent_withdrawal', label: 'Consent Withdrawal' },
  { value: 'data_portability', label: 'Data Portability' },
];

const local = reactive({
  search: '',
  status: '',
  request_type: '',
  identity_verification_status: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      status: value.status || '',
      request_type: value.request_type || '',
      identity_verification_status: value.identity_verification_status || '',
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
    request_type: '',
    identity_verification_status: '',
    page: 1,
  });
  emit('reset');
}
</script>
