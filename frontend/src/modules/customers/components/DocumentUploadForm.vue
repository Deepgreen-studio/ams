<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Document name
        </label>
        <input
          v-model="form.name"
          type="text"
          class="input"
          placeholder="Optional display name"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Category / folder
        </label>
        <SelectBox
          v-model="form.category"
          wrapper-class="w-full"
          size="lg"
          :options="categoryOptions"
          :disabled="loading"
        />
        <p v-if="errors.category" class="mt-1 text-xs text-rose-600">{{ errors.category[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Status
        </label>
        <SelectBox
          v-model="form.status"
          wrapper-class="w-full"
          size="lg"
          :options="statusOptions"
          :disabled="loading"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Expiry date
        </label>
        <input v-model="form.expires_at" type="datetime-local" class="input" :disabled="loading" />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          File
        </label>
        <input type="file" class="input file:mr-3" :disabled="loading" @change="onFileChange" />
        <p v-if="errors.file" class="mt-1 text-xs text-rose-600">{{ errors.file[0] }}</p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Notes
        </label>
        <textarea v-model="form.notes" rows="3" class="input" :disabled="loading" />
      </div>
    </div>

    <div class="flex justify-end gap-2 pt-1">
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading || !file"
      >
        {{ loading ? 'Uploading...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Upload document' },
  defaultCategory: { type: String, default: 'contracts' },
});

const emit = defineEmits(['submit', 'cancel']);

const categoryOptions = [
  { value: 'contracts', label: 'Contracts' },
  { value: 'nda', label: 'NDA' },
  { value: 'invoices', label: 'Invoices' },
  { value: 'certificates', label: 'Certificates' },
  { value: 'attachments', label: 'Attachments' },
  { value: 'custom', label: 'Custom Documents' },
];

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'expired', label: 'Expired' },
];

const form = reactive({
  name: '',
  category: props.defaultCategory || 'contracts',
  status: 'active',
  expires_at: '',
  notes: '',
});

const file = ref(null);

watch(
  () => props.defaultCategory,
  (value) => {
    if (value) form.category = value;
  },
);

function onFileChange(event) {
  file.value = event.target.files?.[0] || null;
}

function onSubmit() {
  if (!file.value || props.loading) return;
  emit('submit', { ...form, file: file.value });
}
</script>

<style scoped>
.input {
  width: 100%;
  height: 3rem;
  border-radius: 12px;
  border: 1px solid #e4e4e7;
  background: #fff;
  padding: 0.5rem 0.875rem;
  font-size: 0.875rem;
  color: #1e293b;
  outline: none;
  box-shadow: none;
}
textarea.input {
  height: auto;
  min-height: 5rem;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
}
.input:focus {
  border-color: var(--color-brand-500, #f97316);
}
.input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
