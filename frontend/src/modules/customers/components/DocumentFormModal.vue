<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[12px] bg-white p-6 shadow-xl sm:p-8"
      role="dialog"
      aria-modal="true"
      aria-labelledby="document-form-title"
    >
      <h3 id="document-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit document' : 'Upload document' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{
          isEdit
            ? 'Update document metadata (name, folder, status, expiry).'
            : 'Add a file to this customer library.'
        }}
      </p>

      <DocumentUploadForm
        v-if="!isEdit"
        :key="`upload-${defaultCategory}`"
        class="mt-5"
        :default-category="defaultCategory"
        :loading="loading"
        :errors="errors"
        :error="error"
        submit-label="Upload document"
        @submit="onSubmit"
        @cancel="onCancel"
      />

      <form v-else class="mt-5 space-y-4" @submit.prevent="onEditSubmit">
        <div
          v-if="error"
          class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
        >
          {{ error }}
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
              Name
            </label>
            <input v-model="editForm.name" type="text" class="input" required :disabled="loading" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
              Category / folder
            </label>
            <SelectBox
              v-model="editForm.category"
              wrapper-class="w-full"
              size="lg"
              :options="categoryOptions"
              :disabled="loading"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
              Status
            </label>
            <SelectBox
              v-model="editForm.status"
              wrapper-class="w-full"
              size="lg"
              :options="statusOptions"
              :disabled="loading"
            />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
              Expiry date
            </label>
            <input
              v-model="editForm.expires_at"
              type="datetime-local"
              class="input"
              :disabled="loading"
            />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
              Notes
            </label>
            <textarea v-model="editForm.notes" rows="3" class="input" :disabled="loading" />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-1">
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
            :disabled="loading"
            @click="onCancel"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="loading"
          >
            {{ loading ? 'Saving...' : 'Save changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import DocumentUploadForm from '@/modules/customers/components/DocumentUploadForm.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  document: { type: Object, default: null },
  defaultCategory: { type: String, default: 'contracts' },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const isEdit = computed(() => Boolean(props.document?.uuid));

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
  { value: 'archived', label: 'Archived' },
];

const editForm = reactive({
  name: '',
  category: 'contracts',
  status: 'active',
  expires_at: '',
  notes: '',
});

watch(
  () => [props.open, props.document],
  () => {
    if (!props.document) return;
    editForm.name = props.document.name || '';
    editForm.category = props.document.category || 'contracts';
    editForm.status = props.document.status || 'active';
    editForm.expires_at = toLocalInput(props.document.expires_at);
    editForm.notes = props.document.notes || '';
  },
  { immediate: true, deep: true },
);

function toLocalInput(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}

function onSubmit(payload) {
  if (props.loading) return;
  emit('submit', payload);
}

function onEditSubmit() {
  if (props.loading) return;
  emit('submit', {
    name: editForm.name,
    category: editForm.category,
    status: editForm.status,
    notes: editForm.notes || null,
    expires_at: editForm.expires_at ? new Date(editForm.expires_at).toISOString() : null,
  });
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
