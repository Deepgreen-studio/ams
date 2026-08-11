<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-[12px] bg-white p-6 shadow-xl sm:p-8"
      role="dialog"
      aria-modal="true"
      aria-labelledby="task-form-title"
    >
      <h3 id="task-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit task' : 'Create task' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{
          isEdit
            ? 'Update this follow-up task or reminder.'
            : 'Schedule follow-ups and reminders for this customer.'
        }}
      </p>

      <TaskForm
        :key="task?.uuid || 'create'"
        class="mt-5"
        :initial="formInitial"
        :loading="loading"
        :errors="errors"
        :error="error"
        :submit-label="submitLabel"
        @submit="$emit('submit', $event)"
        @cancel="onCancel"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import TaskForm from '@/modules/customers/components/TaskForm.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  task: { type: Object, default: null },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const isEdit = computed(() => Boolean(props.task?.uuid));

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Save changes' : 'Save task';
});

const formInitial = computed(() => {
  if (!props.task) return {};
  return {
    title: props.task.title || '',
    description: props.task.description || '',
    priority: props.task.priority || 'medium',
    due_at: toDatetimeLocal(props.task.due_at),
    remind_at: toDatetimeLocal(props.task.remind_at),
  };
});

function toDatetimeLocal(value) {
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
</script>
