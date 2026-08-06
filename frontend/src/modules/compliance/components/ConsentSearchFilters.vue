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
        placeholder="Name, email, IP, device..."
        class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
      />
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
      <select v-model="local.status" class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm">
        <option value="">All</option>
        <option value="granted">Granted</option>
        <option value="withdrawn">Withdrawn</option>
        <option value="pending">Pending</option>
        <option value="expired">Expired</option>
      </select>
    </div>
    <div class="w-full lg:w-44">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Channel</label>
      <select v-model="local.channel" class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm">
        <option value="">All</option>
        <option value="marketing">Marketing</option>
        <option value="analytics">Analytics</option>
        <option value="push_notification">Push</option>
        <option value="email">Email</option>
        <option value="sms">SMS</option>
        <option value="cookie">Cookie</option>
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

const local = reactive({
  search: '',
  status: '',
  channel: '',
  page: 1,
});

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      status: value.status || '',
      channel: value.channel || '',
      page: 1,
    });
  },
  { immediate: true, deep: true }
);

function onSubmit() {
  emit('submit', { ...local, page: 1 });
}

function onReset() {
  Object.assign(local, { search: '', status: '', channel: '', page: 1 });
  emit('reset');
}
</script>
