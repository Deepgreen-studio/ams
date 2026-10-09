<template>
  <form class="space-y-8" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">
          Version Number
        </label>
        <input
          v-model="form.version_number"
          type="text"
          placeholder="1.0.0"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('version_number')"
          :required="!initial.uuid"
        />
        <p class="mt-1.5 text-xs text-slate-500">
          Semantic versioning (MAJOR.MINOR.PATCH). Optional leading “v” is accepted.
        </p>
        <FieldError :message="displayErrors.version_number?.[0] || ''" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Build Number</label>
        <input
          v-model="form.build_number"
          type="text"
          placeholder="100"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('build_number')"
        />
        <FieldError :message="displayErrors.build_number?.[0] || ''" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox
          v-model="form.status"
          size="lg"
          :options="statusOptions"
          :error="Boolean(displayErrors.status)"
        />
        <FieldError :message="displayErrors.status?.[0] || ''" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Release Date</label>
        <input
          v-model="form.release_date"
          type="datetime-local"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('release_date')"
        />
        <FieldError :message="displayErrors.release_date?.[0] || ''" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">
          Minimum Supported Version
        </label>
        <input
          v-model="form.minimum_supported_version"
          type="text"
          placeholder="1.0.0"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('minimum_supported_version')"
        />
        <FieldError :message="displayErrors.minimum_supported_version?.[0] || ''" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Release Notes</label>
        <textarea
          v-model="form.release_notes"
          rows="5"
          placeholder="What's new in this version..."
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('release_notes')"
        />
        <FieldError :message="displayErrors.release_notes?.[0] || ''" />
      </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
      <button
        type="button"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import FieldError from '@/components/ui/FieldError.vue';
import { reactive, watch } from 'vue';
import { useFieldErrors } from '@/composables/useFieldErrors';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { toDateTimeLocalInput } from '@/utils/appTimezone';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});

const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'testing', label: 'Testing' },
  { value: 'beta', label: 'Beta' },
  { value: 'production', label: 'Production' },
  { value: 'deprecated', label: 'Deprecated' },
  { value: 'rollback', label: 'Rollback' },
];

const form = reactive(createForm(props.initial));
const { displayErrors } = useFieldErrors(form, () => props.errors);

watch(
  () => props.initial,
  (value) => Object.assign(form, createForm(value)),
  { deep: true },
);

watch(
  () => props.error,
  (message) => {
    if (!message) return;

    const firstFieldError = Object.values(props.errors || {})
      .flat()
      .find(Boolean);

    toast.error(firstFieldError || message, 'Validation Failed');
  },
);

function createForm(value = {}) {
  return {
    version_number: value.version_number || '',
    build_number: value.build_number || '',
    status: value.status || 'draft',
    release_date: toLocalInput(value.release_date),
    minimum_supported_version: value.minimum_supported_version || '',
    release_notes: value.release_notes || '',
  };
}

function toLocalInput(value) {
  return toDateTimeLocalInput(value);
}

function fieldClass(field) {
  return displayErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

function onSubmit() {
  emit('submit', {
    version_number: form.version_number,
    build_number: form.build_number || null,
    status: form.status,
    release_date: form.release_date ? new Date(form.release_date).toISOString() : null,
    minimum_supported_version: form.minimum_supported_version || null,
    release_notes: form.release_notes || null,
  });
}
</script>
