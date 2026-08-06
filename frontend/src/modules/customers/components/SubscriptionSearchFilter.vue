<template>
  <form
    class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:flex-wrap md:items-end"
    @submit.prevent="onSubmit"
  >
    <div class="min-w-[12rem] flex-1">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Search</label
      >
      <input
        v-model="local.search"
        type="search"
        placeholder="Plan, notes..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
    <div class="w-full md:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Status</label
      >
      <select
        v-model="local.status"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
      >
        <option value="">All</option>
        <option value="trialing">Trialing</option>
        <option value="active">Active</option>
        <option value="past_due">Past due</option>
        <option value="suspended">Suspended</option>
        <option value="cancelled">Cancelled</option>
        <option value="expired">Expired</option>
      </select>
    </div>
    <div class="w-full md:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Plan</label
      >
      <select
        v-model="local.plan_type"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
      >
        <option value="">All</option>
        <option value="trial">Trial</option>
        <option value="monthly">Monthly</option>
        <option value="yearly">Yearly</option>
        <option value="lifetime">Lifetime</option>
        <option value="enterprise">Enterprise</option>
      </select>
    </div>
    <div class="w-full md:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >Payment</label
      >
      <select
        v-model="local.payment_status"
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
      >
        <option value="">All</option>
        <option value="not_required">Not required</option>
        <option value="pending">Pending</option>
        <option value="paid">Paid</option>
        <option value="failed">Failed</option>
        <option value="past_due">Past due</option>
        <option value="refunded">Refunded</option>
      </select>
    </div>
    <div class="flex gap-2">
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Filter
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
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

const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const local = reactive({
  search: '',
  status: '',
  plan_type: '',
  payment_status: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.status = value.status || '';
    local.plan_type = value.plan_type || '';
    local.payment_status = value.payment_status || '';
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  const payload = {
    search: local.search,
    status: local.status,
    plan_type: local.plan_type,
    payment_status: local.payment_status,
    page: 1,
  };
  emit('update:modelValue', { ...props.modelValue, ...payload });
  emit('submit', payload);
}

function onReset() {
  local.search = '';
  local.status = '';
  local.plan_type = '';
  local.payment_status = '';
  emit('reset');
}
</script>
