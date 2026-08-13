<template>
  <form class="space-y-5" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companySelectOptions"
          :disabled="loading"
          :error="Boolean(fieldError('company_id'))"
        />
        <p v-if="fieldError('company_id')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('company_id') }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
        <input
          v-model="form.title"
          type="text"
          class="input"
          required
          maxlength="255"
          placeholder="Short incident title"
          :disabled="loading"
        />
        <p v-if="fieldError('title')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('title') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Breach type</label>
        <SelectBox
          v-model="form.breach_type"
          size="lg"
          :options="breachTypeOptions"
          :disabled="loading"
          :error="Boolean(fieldError('breach_type'))"
        />
        <p v-if="fieldError('breach_type')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('breach_type') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Severity</label>
        <SelectBox
          v-model="form.severity"
          size="lg"
          :options="breachSeverityOptions"
          :disabled="loading"
          :error="Boolean(fieldError('severity'))"
        />
        <p v-if="fieldError('severity')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('severity') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Discovered at</label>
        <input
          v-model="form.discovered_at"
          type="datetime-local"
          class="input"
          :disabled="loading"
        />
        <p v-if="fieldError('discovered_at')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('discovered_at') }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Occurred at</label>
        <input
          v-model="form.occurred_at"
          type="datetime-local"
          class="input"
          :disabled="loading"
        />
        <p v-if="fieldError('occurred_at')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('occurred_at') }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="5"
          class="input"
          maxlength="20000"
          placeholder="What happened, who is affected, and what is known so far."
          :disabled="loading"
        />
        <p v-if="fieldError('description')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('description') }}
        </p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Affected data categories</label>
        <input
          v-model="categoriesInput"
          type="text"
          class="input"
          placeholder="email, name, phone (comma separated)"
          :disabled="loading"
        />
        <p class="mt-1.5 text-xs text-slate-500">Comma-separated categories stored on the incident.</p>
        <p v-if="fieldError('affected_data_categories')" class="mt-1 text-xs text-rose-600">
          {{ fieldError('affected_data_categories') }}
        </p>
      </div>
      <label class="flex items-center gap-2.5 text-sm text-slate-700">
        <input
          v-model="form.personal_data_involved"
          type="checkbox"
          class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
          :disabled="loading"
        />
        Personal data involved
      </label>
      <label class="flex items-center gap-2.5 text-sm text-slate-700">
        <input
          v-model="form.special_category_data"
          type="checkbox"
          class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
          :disabled="loading"
        />
        Special category data
      </label>
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
        {{ loading ? 'Saving…' : 'Report incident' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { breachSeverityOptions, breachTypeOptions } from '@/modules/compliance/utils/breachOptions';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  fieldErrors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const categoriesInput = ref('email, name');
const form = reactive({
  company_id: '',
  title: '',
  description: '',
  breach_type: 'unauthorized_access',
  severity: 'medium',
  discovered_at: '',
  occurred_at: '',
  personal_data_involved: true,
  special_category_data: false,
});

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const canSubmit = computed(() => Boolean(form.company_id && form.title && form.breach_type));

function fieldError(key) {
  const value = props.fieldErrors?.[key];
  return Array.isArray(value) ? value[0] : value || '';
}

onMounted(async () => {
  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function onSubmit() {
  if (!canSubmit.value || props.loading) {
    return;
  }

  emit('submit', {
    company_id: form.company_id,
    title: form.title,
    description: form.description || null,
    breach_type: form.breach_type,
    severity: form.severity,
    discovered_at: form.discovered_at || null,
    occurred_at: form.occurred_at || null,
    personal_data_involved: form.personal_data_involved,
    special_category_data: form.special_category_data,
    affected_data_categories: categoriesInput.value
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean),
  });
}
</script>
