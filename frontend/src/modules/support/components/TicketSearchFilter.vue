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
        placeholder="Search ticket #, subject…"
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
        v-model="local.priority"
        wrapper-class="min-w-[9.5rem]"
        :options="prioritySelectOptions"
      />
      <SelectBox
        v-model="local.category"
        wrapper-class="min-w-[10.5rem]"
        :options="categorySelectOptions"
      />
      <SelectBox
        v-model="local.company"
        wrapper-class="min-w-[10.5rem]"
        :options="companySelectOptions"
      />
      <SelectBox
        v-model="local.trashed"
        wrapper-class="min-w-[10rem]"
        :options="trashedOptions"
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { companyService } from '@/modules/companies/services/companyService';
import SelectBox from '@/modules/users/components/SelectBox.vue';
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

const statusSelectOptions = [{ value: '', label: 'All statuses' }, ...statusOptions];
const prioritySelectOptions = [{ value: '', label: 'All priorities' }, ...priorityOptions];
const categorySelectOptions = [{ value: '', label: 'All categories' }, ...categoryOptions];
const trashedOptions = [
  { value: '', label: 'Exclude archived' },
  { value: 'with', label: 'Include archived' },
  { value: 'only', label: 'Only archived' },
];

const companySelectOptions = computed(() => [
  { value: '', label: 'All companies' },
  ...companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
]);

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
  { immediate: true, deep: true },
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
