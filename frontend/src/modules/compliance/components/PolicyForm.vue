<template>
  <form class="space-y-5" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
        <SelectBox
          v-model="form.company_id"
          size="lg"
          placeholder="Select company"
          :options="companySelectOptions"
          :disabled="loading || Boolean(initial?.uuid)"
          :error="Boolean(fieldError('company_id'))"
        />
        <FieldError :message="fieldError('company_id')" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Policy Type</label>
        <SelectBox
          v-model="form.policy_type"
          size="lg"
          placeholder="Select type"
          :options="policyTypeOptions"
          :disabled="loading"
          :error="Boolean(fieldError('policy_type'))"
        />
        <FieldError :message="fieldError('policy_type')" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
        <input
          v-model="form.title"
          type="text"
          class="input"
          required
          maxlength="255"
          placeholder="Policy title"
          :disabled="loading"
        />
        <FieldError :message="fieldError('title')" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="2"
          class="input"
          maxlength="2000"
          placeholder="Optional summary for reviewers"
          :disabled="loading"
        />
        <FieldError :message="fieldError('description')" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Body</label>
        <textarea
          v-model="form.body"
          rows="10"
          class="input font-mono text-sm"
          required
          placeholder="Policy content"
          :disabled="loading"
        />
        <FieldError :message="fieldError('body')" />
      </div>
      <div v-if="initial?.uuid" class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Change Summary</label>
        <input
          v-model="form.change_summary"
          type="text"
          class="input"
          maxlength="255"
          placeholder="Why this revision exists (stored on the new version)"
          :disabled="loading"
        />
        <FieldError :message="fieldError('change_summary')" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Effective At</label>
        <input
          v-model="form.effective_at"
          type="datetime-local"
          class="input"
          :disabled="loading"
        />
        <FieldError :message="fieldError('effective_at')" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Review Due</label>
        <input
          v-model="form.review_due_at"
          type="date"
          class="input"
          :disabled="loading"
        />
        <FieldError :message="fieldError('review_due_at')" />
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
        {{ loading ? 'Saving…' : initial?.uuid ? 'Save as new version' : 'Create policy' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import FieldError from '@/components/ui/FieldError.vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useFieldErrors } from '@/composables/useFieldErrors';
import { companyService } from '@/modules/companies/services/companyService';
import { policyTypeOptions } from '@/modules/compliance/utils/policyOptions';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  initial: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  fieldErrors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const form = reactive({
  company_id: '',
  title: '',
  policy_type: 'privacy_policy',
  description: '',
  body: '',
  change_summary: '',
  effective_at: '',
  review_due_at: '',
});

const { fieldMessage: fieldError } = useFieldErrors(form, () => props.fieldErrors);

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const canSubmit = computed(() =>
  Boolean(form.title && form.policy_type && form.body && (props.initial?.uuid || form.company_id)),
);

function hydrate() {
  const item = props.initial || {};
  form.company_id = item.company?.uuid || item.company_id || '';
  form.title = item.title || '';
  form.policy_type = item.policy_type || 'privacy_policy';
  form.description = item.description || '';
  form.body = item.body || '';
  form.change_summary = '';
  form.effective_at = item.effective_at ? String(item.effective_at).slice(0, 16) : '';
  form.review_due_at = item.review_due_at || '';
}

watch(() => props.initial, hydrate, { immediate: true });

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

  const payload = {
    title: form.title,
    policy_type: form.policy_type,
    description: form.description || null,
    body: form.body,
    review_due_at: form.review_due_at || null,
    effective_at: form.effective_at || null,
  };
  if (!props.initial?.uuid) {
    payload.company_id = form.company_id;
  } else if (form.change_summary) {
    payload.change_summary = form.change_summary;
  }
  emit('submit', payload);
}
</script>
