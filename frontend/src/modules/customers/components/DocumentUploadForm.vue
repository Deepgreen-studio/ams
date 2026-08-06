<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Document name</label>
        <input v-model="form.name" type="text" class="input" placeholder="Optional display name" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Category / folder</label>
        <select v-model="form.category" class="input" required>
          <option value="contracts">Contracts</option>
          <option value="nda">NDA</option>
          <option value="invoices">Invoices</option>
          <option value="certificates">Certificates</option>
          <option value="attachments">Attachments</option>
          <option value="custom">Custom Documents</option>
        </select>
        <p v-if="errors.category" class="mt-1 text-xs text-rose-600">{{ errors.category[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Expiry date</label>
        <input v-model="form.expires_at" type="datetime-local" class="input" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">File</label>
        <input type="file" class="input" required @change="onFileChange" />
        <p v-if="errors.file" class="mt-1 text-xs text-rose-600">{{ errors.file[0] }}</p>
      </div>

      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
        <textarea v-model="form.notes" rows="3" class="input" />
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('cancel')">
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading || !file"
      >
        {{ loading ? 'Uploading...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Upload document' },
  defaultCategory: { type: String, default: 'contracts' },
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive({
  name: '',
  category: props.defaultCategory || 'contracts',
  status: 'active',
  expires_at: '',
  notes: '',
});

const file = ref(null);

function onFileChange(event) {
  file.value = event.target.files?.[0] || null;
}

function onSubmit() {
  emit('submit', { ...form, file: file.value });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
