<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Type
        </label>
        <SelectBox
          v-model="form.type"
          wrapper-class="w-full"
          size="lg"
          :options="typeOptions"
          :disabled="loading"
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Direction
        </label>
        <SelectBox
          v-model="form.direction"
          wrapper-class="w-full"
          size="lg"
          :options="directionOptions"
          :disabled="loading"
        />
      </div>
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Subject
      </label>
      <input v-model="form.subject" type="text" class="input" :disabled="loading" />
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Summary / body
      </label>
      <textarea v-model="form.body" rows="4" class="input" :disabled="loading" />
    </div>

    <div v-if="form.type === 'call'">
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Duration (seconds)
      </label>
      <input
        v-model="form.duration_seconds"
        type="number"
        min="0"
        class="input"
        :disabled="loading"
      />
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
        Occurred at
      </label>
      <input v-model="form.occurred_at" type="datetime-local" class="input" :disabled="loading" />
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
  submitLabel: { type: String, default: 'Log entry' },
  initial: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit', 'cancel']);

const typeOptions = [
  { value: 'email', label: 'Email' },
  { value: 'call', label: 'Call log' },
  { value: 'meeting', label: 'Meeting' },
];

const directionOptions = [
  { value: 'outbound', label: 'Outbound' },
  { value: 'inbound', label: 'Inbound' },
  { value: 'internal', label: 'Internal' },
];

const form = reactive({
  type: 'email',
  direction: 'outbound',
  subject: '',
  body: '',
  duration_seconds: '',
  occurred_at: '',
});

watch(
  () => props.initial,
  (value) => {
    form.type = value?.type || 'email';
    form.direction = value?.direction || 'outbound';
    form.subject = value?.subject || '';
    form.body = value?.body || '';
    form.duration_seconds = value?.duration_seconds || '';
    form.occurred_at = value?.occurred_at || '';
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  emit('submit', {
    type: form.type,
    direction: form.direction,
    subject: form.subject || null,
    body: form.body || null,
    duration_seconds: form.duration_seconds ? Number(form.duration_seconds) : null,
    occurred_at: form.occurred_at || null,
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
