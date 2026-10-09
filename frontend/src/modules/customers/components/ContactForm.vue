<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Contact Type
        </label>
        <SelectBox
          v-model="form.contact_type"
          wrapper-class="w-full"
          size="lg"
          :options="typeOptions"
          :disabled="loading"
        />
        <FieldError :message="displayErrors.contact_type?.[0] || ''" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Responsibilities
        </label>
        <div class="flex flex-wrap gap-2">
          <label
            v-for="option in typeOptions"
            :key="option.value"
            class="inline-flex items-center gap-2 rounded-full border border-zinc-200 px-3 py-1.5 text-xs text-slate-700"
          >
            <input v-model="form.responsibilities" type="checkbox" :value="option.value" :disabled="loading" />
            {{ option.label }}
          </label>
        </div>
        <p class="mt-1 text-xs text-slate-500">A contact can carry more than one responsibility. The contact type is the primary one.</p>
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
        <FieldError :message="displayErrors.name?.[0] || ''" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Email
        </label>
        <input v-model="form.email" type="email" class="input" :disabled="loading" />
        <FieldError :message="displayErrors.email?.[0] || ''" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Phone
        </label>
        <PhoneInput
          v-model="form.phone"
          :disabled="loading"
          :error="Boolean(phoneError || displayErrors.phone)"
        />
        <FieldError :message="phoneError || displayErrors.phone?.[0] || ''" />
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
import FieldError from '@/components/ui/FieldError.vue';
import { nextTick, onMounted, reactive, ref, watch } from 'vue';
import { useFieldErrors } from '@/composables/useFieldErrors';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { isValidE164, PHONE_INVALID_MESSAGE } from '@/utils/phone';
import { CONTACT_TYPE_OPTIONS } from '@/modules/customers/constants/customerModel';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});

const emit = defineEmits(['submit', 'cancel']);

const nameInput = ref(null);
const phoneError = ref('');
const form = reactive(createForm(props.initial));
const { displayErrors } = useFieldErrors(form, () => props.errors);

const typeOptions = CONTACT_TYPE_OPTIONS;

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
    responsibilities: Array.isArray(value.responsibilities) && value.responsibilities.length
      ? [...value.responsibilities]
      : [value.contact_type || 'support'],
    name: value.name || '',
    email: value.email || '',
    phone: value.phone || '',
    position: value.position || '',
    department: value.department || '',
    status: value.status || 'active',
    notes: value.notes || '',
  };
}

watch(
  () => form.phone,
  (value) => {
    if (!phoneError.value) return;
    if (!value || isValidE164(value)) phoneError.value = '';
  },
);

function onSubmit() {
  phoneError.value = '';
  if (form.phone && !isValidE164(form.phone)) {
    phoneError.value = PHONE_INVALID_MESSAGE;
    return;
  }

  const responsibilities = [...new Set([form.contact_type, ...(form.responsibilities || [])].filter(Boolean))];
  emit('submit', { ...form, responsibilities, phone: form.phone || null });
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
