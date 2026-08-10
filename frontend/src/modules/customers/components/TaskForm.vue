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
        Title
      </label>
      <input v-model="form.title" type="text" required class="input" :disabled="loading" />
      <p v-if="errors.title" class="mt-1 text-xs text-rose-600">{{ errors.title[0] }}</p>
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Description
      </label>
      <textarea
        v-model="form.description"
        rows="3"
        class="input"
        placeholder="Optional details"
        :disabled="loading"
      />
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Priority
      </label>
      <SelectBox
        v-model="form.priority"
        wrapper-class="w-full"
        size="lg"
        :options="priorityOptions"
        :disabled="loading"
      />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Due at
        </label>
        <input v-model="form.due_at" type="datetime-local" class="input" :disabled="loading" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Remind at
        </label>
        <input v-model="form.remind_at" type="datetime-local" class="input" :disabled="loading" />
      </div>
    </div>

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
  submitLabel: { type: String, default: 'Save task' },
  initial: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'cancel']);

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
];

const form = reactive({
  title: '',
  description: '',
  priority: 'medium',
  due_at: '',
  remind_at: '',
});

watch(
  () => props.initial,
  (value) => {
    form.title = value?.title || '';
    form.description = value?.description || '';
    form.priority = value?.priority || 'medium';
    form.due_at = value?.due_at || '';
    form.remind_at = value?.remind_at || '';
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  emit('submit', {
    title: form.title,
    description: form.description || null,
    priority: form.priority,
    due_at: form.due_at || null,
    remind_at: form.remind_at || null,
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
