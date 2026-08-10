<template>
  <form class="space-y-5" @submit.prevent="onSubmit">
    <div class="grid gap-5 md:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Display name</label>
        <input
          v-model="form.display_name"
          type="text"
          placeholder="Operations Manager"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          @input="onDisplayNameInput"
        />
        <p v-if="errors.display_name" class="mt-1 text-xs text-rose-600">
          {{ errors.display_name[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Machine name</label>
        <input
          v-model="form.name"
          type="text"
          :disabled="lockName"
          placeholder="operations-manager"
          class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0 disabled:bg-slate-50"
          @input="nameTouched = true"
        />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="4"
          placeholder="Brief description of this role"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
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
import { reactive, ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save role' },
  lockName: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();

const form = reactive(createForm(props.initial));
const nameTouched = ref(Boolean(props.initial?.name));

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, createForm(value));
    nameTouched.value = Boolean(value?.name);
  },
  { deep: true }
);

watch(
  () => props.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  }
);

function slugify(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80);
}

function createForm(value = {}) {
  return {
    name: value.name || '',
    display_name: value.display_name || '',
    description: value.description || '',
  };
}

function onDisplayNameInput() {
  if (props.lockName || nameTouched.value) {
    return;
  }
  form.name = slugify(form.display_name);
}

function onSubmit() {
  if (!form.name && form.display_name) {
    form.name = slugify(form.display_name);
  }
  emit('submit', { ...form });
}
</script>
