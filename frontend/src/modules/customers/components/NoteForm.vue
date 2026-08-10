<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Note type
      </label>
      <SelectBox
        v-model="form.note_type"
        wrapper-class="w-full"
        size="lg"
        :options="typeOptions"
        :disabled="loading"
      />
      <p v-if="errors.note_type" class="mt-1 text-xs text-rose-600">{{ errors.note_type[0] }}</p>
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Title
      </label>
      <input
        v-model="form.title"
        type="text"
        class="input"
        placeholder="Optional title"
        :disabled="loading"
      />
      <p v-if="errors.title" class="mt-1 text-xs text-rose-600">{{ errors.title[0] }}</p>
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Body
      </label>
      <textarea
        v-model="form.body"
        rows="4"
        required
        class="input"
        placeholder="Write a note..."
        :disabled="loading"
      />
      <p v-if="errors.body" class="mt-1 text-xs text-rose-600">{{ errors.body[0] }}</p>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
      <input
        v-model="form.is_pinned"
        type="checkbox"
        class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
        :disabled="loading"
      />
      Pin note
    </label>

    <div class="flex flex-wrap justify-end gap-2 pt-2">
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
        {{ submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Save note' },
  initial: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'cancel']);

const typeOptions = [
  { value: 'general', label: 'Note' },
  { value: 'internal', label: 'Internal comment' },
  { value: 'meeting', label: 'Meeting note' },
];

const form = reactive({
  note_type: 'general',
  title: '',
  body: '',
  is_pinned: false,
});

watch(
  () => props.initial,
  (value) => {
    form.note_type = value?.note_type || 'general';
    form.title = value?.title || '';
    form.body = value?.body || '';
    form.is_pinned = Boolean(value?.is_pinned);
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  emit('submit', {
    note_type: form.note_type,
    title: form.title || null,
    body: form.body,
    is_pinned: form.is_pinned,
  });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e4e4e7;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  outline: none;
  box-shadow: none;
}
.input:focus {
  border-color: #f97316;
}
.input:disabled {
  background: #fafafa;
  opacity: 0.7;
}
</style>
