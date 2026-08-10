<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', form)">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Contact type
        </label>
        <SelectBox
          v-model="form.contact_type"
          wrapper-class="w-full"
          size="lg"
          :options="typeOptions"
          :disabled="loading"
        />
        <p v-if="errors.contact_type" class="mt-1 text-xs text-rose-600">
          {{ errors.contact_type[0] }}
        </p>
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
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Name
        </label>
        <input
          ref="nameInput"
          v-model="form.name"
          type="text"
          required
          class="input"
          :disabled="loading"
          @keydown.esc.prevent="$emit('cancel')"
        />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Email
        </label>
        <input v-model="form.email" type="email" class="input" :disabled="loading" />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Phone
        </label>
        <input v-model="form.phone" type="text" class="input" :disabled="loading" />
        <p v-if="errors.phone" class="mt-1 text-xs text-rose-600">{{ errors.phone[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Position
        </label>
        <input v-model="form.position" type="text" class="input" :disabled="loading" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Department
        </label>
        <input v-model="form.department" type="text" class="input" :disabled="loading" />
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
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { nextTick, onMounted, reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});

defineEmits(['submit', 'cancel']);

const nameInput = ref(null);
const form = reactive(createForm(props.initial));

const typeOptions = [
  { value: 'primary', label: 'Primary' },
  { value: 'technical', label: 'Technical' },
  { value: 'billing', label: 'Billing' },
  { value: 'support', label: 'Support' },
  { value: 'emergency', label: 'Emergency' },
];

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

watch(
  () => props.initial,
  async (value) => {
    Object.assign(form, createForm(value));
    await nextTick();
    nameInput.value?.focus();
  },
  { deep: true },
);

onMounted(async () => {
  await nextTick();
  nameInput.value?.focus();
});

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
