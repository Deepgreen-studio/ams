<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="relative min-w-0 flex-1 lg:max-w-sm">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        v-model="local.search"
        type="search"
        placeholder="Name, email, company, industry..."
        class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        @keyup.enter="emitSubmit"
      />
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <SelectBox
        v-model="local.customer_type"
        wrapper-class="min-w-[9.5rem]"
        :options="typeOptions"
        @change="emitSubmit"
      />

      <SelectBox
        v-model="local.status"
        wrapper-class="min-w-[9.5rem]"
        :options="statusOptions"
        @change="emitSubmit"
      />

      <SelectBox
        v-model="local.company"
        wrapper-class="min-w-[11rem]"
        :options="companyOptions"
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { companyService } from '@/modules/companies/services/companyService';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue', 'submit', 'reset']);

const companies = ref([]);

const typeOptions = [
  { value: '', label: 'Type: All' },
  { value: 'individual', label: 'Individual' },
  { value: 'business', label: 'Business' },
  { value: 'enterprise', label: 'Enterprise' },
];

const statusOptions = [
  { value: '', label: 'Status: All' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'pending', label: 'Pending' },
];

const trashedOptions = [
  { value: '', label: 'Deleted: Exclude' },
  { value: 'with', label: 'Include deleted' },
  { value: 'only', label: 'Only deleted' },
];

const companyOptions = computed(() => [
  { value: '', label: 'All companies' },
  ...companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
]);

const local = reactive({
  search: props.modelValue.search || '',
  status: props.modelValue.status || '',
  customer_type: props.modelValue.customer_type || '',
  company: props.modelValue.company || '',
  trashed: props.modelValue.trashed || '',
});

watch(
  () => props.modelValue,
  (value) => {
    local.search = value.search || '';
    local.status = value.status || '';
    local.customer_type = value.customer_type || '';
    local.company = value.company || '';
    local.trashed = value.trashed || '';
  },
  { deep: true },
);

onMounted(async () => {
  try {
    const { data } = await companyService.list({
      per_page: 100,
      sort_by: 'company_name',
      sort_dir: 'asc',
    });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function emitSubmit() {
  emit('update:modelValue', { ...props.modelValue, ...local, page: 1 });
  emit('submit', { ...local, page: 1 });
}

function emitReset() {
  local.search = '';
  local.status = '';
  local.customer_type = '';
  local.company = '';
  local.trashed = '';
  emit('reset');
}
</script>
