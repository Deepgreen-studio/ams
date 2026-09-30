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
        placeholder="Number, name, email..."
        class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        @input="onSearchInput"
        @search="onSearchInput"
      />
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <SelectBox
        v-model="local.status"
        wrapper-class="min-w-[9.5rem]"
        :options="statusSelectOptions"
      />
      <SelectBox
        v-model="local.request_type"
        wrapper-class="min-w-[11rem]"
        :options="typeSelectOptions"
      />
      <SelectBox
        v-model="local.identity_verification_status"
        wrapper-class="min-w-[10.5rem]"
        :options="identityOptions"
      />
      <button
        type="submit"
        class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Apply Filter
      </button>
      <button
        type="button"
        class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="onReset"
      >
        Reset Filter
      </button>
    </div>
  </form>
</template>

<script setup>
import { onBeforeUnmount, reactive, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';

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

const statusSelectOptions = [{ value: '', label: 'Status: All' }, ...statusOptions];
const typeSelectOptions = [{ value: '', label: 'Type: All' }, ...typeOptions];
const identityOptions = [
  { value: '', label: 'Identity: All' },
  { value: 'pending', label: 'Pending' },
  { value: 'verified', label: 'Verified' },
  { value: 'failed', label: 'Failed' },
  { value: 'not_required', label: 'Not required' },
];

const local = reactive({
  search: '',
  status: '',
  request_type: '',
  identity_verification_status: '',
});

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      status: value.status || '',
      request_type: value.request_type || '',
      identity_verification_status: value.identity_verification_status || '',
    });
  },
  { immediate: true, deep: true },
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
  });
  emit('reset');
}

let searchTimer = null;

function onSearchInput() {
  window.clearTimeout(searchTimer);
  const delay = String(local.search || '').trim() ? 300 : 0;
  searchTimer = window.setTimeout(() => onSubmit(), delay);
}

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
});
</script>
