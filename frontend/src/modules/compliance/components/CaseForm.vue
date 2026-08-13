<template>
  <form class="space-y-5" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="!hideCompany">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companySelectOptions"
          :disabled="loading || Boolean(initial.uuid)"
          :error="Boolean(fieldError('company_id'))"
        />
        <p v-if="fieldError('company_id')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('company_id') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Case type</label>
        <SelectBox
          v-model="form.case_type"
          size="lg"
          :options="typeOptions"
          :disabled="loading"
          :error="Boolean(fieldError('case_type'))"
        />
        <p v-if="fieldError('case_type')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('case_type') }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
        <input
          v-model="form.title"
          type="text"
          class="input"
          maxlength="255"
          placeholder="Case title"
          :disabled="loading"
        />
        <p v-if="fieldError('title')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('title') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Priority</label>
        <SelectBox
          v-model="form.priority"
          size="lg"
          :options="priorityOptions"
          :disabled="loading"
          :error="Boolean(fieldError('priority'))"
        />
        <p v-if="fieldError('priority')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('priority') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox
          v-model="form.status"
          size="lg"
          :options="statusOptions"
          :disabled="loading"
          :error="Boolean(fieldError('status'))"
        />
        <p v-if="fieldError('status')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('status') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Assign to</label>
        <SelectBox
          v-model="form.assigned_to"
          size="lg"
          placeholder="Unassigned"
          :options="assigneeSelectOptions"
          :disabled="loading"
          :error="Boolean(fieldError('assigned_to'))"
        />
        <p v-if="fieldError('assigned_to')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('assigned_to') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Due date</label>
        <input
          v-model="form.due_date"
          type="date"
          class="input"
          :disabled="loading"
        />
        <p v-if="fieldError('due_date')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('due_date') }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="6"
          class="input"
          placeholder="Optional context for investigators."
          :disabled="loading"
        />
        <p v-if="fieldError('description')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('description') }}
        </p>
      </div>
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-5">
      <button
        type="button"
        class="inline-flex h-11 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="inline-flex h-11 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading || !canSubmit"
      >
        {{ loading ? 'Saving…' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { priorityOptions, statusOptions, typeOptions } from '@/modules/compliance/utils/caseOptions';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { userService } from '@/modules/users/services/userService';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  fieldErrors: { type: Object, default: () => ({}) },
  submitLabel: { type: String, default: 'Save case' },
  hideCompany: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const users = ref([]);
const localErrors = ref({});

const form = reactive({
  company_id: '',
  title: '',
  description: '',
  case_type: 'compliance_case',
  priority: 'medium',
  status: 'open',
  assigned_to: '',
  due_date: '',
});

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const assigneeSelectOptions = computed(() => [
  { value: '', label: 'Unassigned' },
  ...users.value.map((user) => ({
    value: user.uuid,
    label: user.email ? `${user.full_name} (${user.email})` : user.full_name,
  })),
]);

const canSubmit = computed(() => {
  const hasCompany = props.hideCompany || Boolean(props.initial.uuid) || Boolean(form.company_id);
  return Boolean(hasCompany && form.case_type && form.title && form.priority && form.status);
});

function fieldError(key) {
  const local = localErrors.value?.[key];
  const remote = props.fieldErrors?.[key];
  const value = local || remote;
  return Array.isArray(value) ? value[0] : value || '';
}

function syncFromInitial() {
  form.company_id = props.initial.company?.uuid || props.initial.company_id || '';
  form.title = props.initial.title || '';
  form.description = props.initial.description || '';
  form.case_type = props.initial.case_type || 'compliance_case';
  form.priority = props.initial.priority || 'medium';
  form.status = props.initial.status || 'open';
  form.assigned_to = props.initial.assignee?.uuid || '';
  form.due_date = props.initial.due_date || '';
  localErrors.value = {};
}

watch(() => props.initial, syncFromInitial, { immediate: true, deep: true });

watch(
  () => props.fieldErrors,
  () => {
    localErrors.value = {};
  },
  { deep: true },
);

onMounted(async () => {
  try {
    const [{ data: companyData }, { data: userData }] = await Promise.all([
      companyService.list({ per_page: 100, status: 'active' }),
      userService.list({ per_page: 100 }),
    ]);
    companies.value = companyData.data?.companies?.items ?? [];
    users.value = userData.data?.users?.items ?? userData.data?.users ?? [];
  } catch {
    companies.value = [];
    users.value = [];
  }
});

function onSubmit() {
  if (!canSubmit.value || props.loading) {
    return;
  }

  localErrors.value = {};

  const payload = {
    title: form.title,
    description: form.description || null,
    case_type: form.case_type,
    priority: form.priority,
    status: form.status,
    assigned_to: form.assigned_to || null,
    due_date: form.due_date || null,
  };

  if (!props.initial.uuid) {
    payload.company_id = form.company_id;
  }

  emit('submit', payload);
}
</script>
