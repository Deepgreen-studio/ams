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
        placeholder="Ticket #, subject, description..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
      <select v-model="local.status" class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500">
        <option value="">All</option>
        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Priority</label>
      <select v-model="local.priority" class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500">
        <option value="">All</option>
        <option v-for="option in priorityOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
    </div>
    <div class="w-full lg:w-48">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
      <select v-model="local.category" class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500">
        <option value="">All</option>
        <option v-for="option in categoryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
    </div>
    <div class="w-full lg:w-48">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Company</label>
      <select v-model="local.company" class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500">
        <option value="">All companies</option>
        <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
          {{ company.company_name }}
        </option>
      </select>
    </div>
    <div class="w-full lg:w-40">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Archived</label>
      <select v-model="local.trashed" class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500">
        <option value="">Exclude</option>
        <option value="with">Include</option>
        <option value="only">Only archived</option>
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
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
import { onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { categoryOptions, priorityOptions, statusOptions } from '@/modules/support/utils/ticketOptions';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'reset', 'update:modelValue']);

const companies = ref([]);
const local = reactive({
  search: '',
  status: '',
  priority: '',
  category: '',
  company: '',
  trashed: '',
});

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(local, {
      search: value.search || '',
      status: value.status || '',
      priority: value.priority || '',
      category: value.category || '',
      company: value.company || '',
      trashed: value.trashed || '',
    });
  },
  { immediate: true, deep: true }
);

onMounted(async () => {
  try {
    const { data } = await companyService.list({ per_page: 100 });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function onSubmit() {
  const payload = { ...local, page: 1 };
  emit('update:modelValue', payload);
  emit('submit', payload);
}

function onReset() {
  Object.assign(local, {
    search: '',
    status: '',
    priority: '',
    category: '',
    company: '',
    trashed: '',
  });
  emit('reset');
}
</script>
