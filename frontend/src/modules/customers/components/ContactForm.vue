<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Contact type</label>
        <select v-model="form.contact_type" class="input" required>
          <option value="primary">Primary</option>
          <option value="technical">Technical</option>
          <option value="billing">Billing</option>
          <option value="support">Support</option>
          <option value="emergency">Emergency</option>
        </select>
        <p v-if="errors.contact_type" class="mt-1 text-xs text-rose-600">{{ errors.contact_type[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
        <input v-model="form.name" type="text" class="input" required />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input v-model="form.email" type="email" class="input" />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
        <input v-model="form.phone" type="text" class="input" />
        <p v-if="errors.phone" class="mt-1 text-xs text-rose-600">{{ errors.phone[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Position</label>
        <input v-model="form.position" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Department</label>
        <input v-model="form.department" type="text" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
        <textarea v-model="form.notes" rows="3" class="input" />
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});

defineEmits(['submit', 'cancel']);

const form = reactive(createForm(props.initial));

watch(
  () => props.initial,
  (value) => Object.assign(form, createForm(value)),
  { deep: true }
);

function createForm(value = {}) {
  return {
    contact_type: value.contact_type || 'support',
    name: value.name || '',
    email: value.email || '',
    phone: value.phone || '',
    position: value.position || '',
    department: value.department || '',
    status: value.status || 'active',
    notes: value.notes || '',
  };
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
