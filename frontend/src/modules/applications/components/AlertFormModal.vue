<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="w-full max-w-lg rounded-[12px] bg-white p-6 shadow-xl sm:p-8"
      role="dialog"
      aria-modal="true"
      aria-labelledby="alert-form-title"
    >
      <h3 id="alert-form-title" class="text-lg font-semibold text-slate-900">Create alert rule</h3>
      <p class="mt-1 text-sm text-slate-500">
        Threshold rules for crash rate, health score, response time, and more.
      </p>

      <form class="mt-5 space-y-4" @submit.prevent="onSubmit">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700" for="alert-name">
            Name
          </label>
          <input
            id="alert-name"
            ref="nameInput"
            v-model="form.name"
            type="text"
            required
            autocomplete="off"
            placeholder="e.g. Low health score"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :disabled="loading"
            @keydown.esc.prevent="onCancel"
          />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Metric</label>
            <SelectBox
              v-model="form.metric"
              size="lg"
              wrapper-class="w-full"
              :options="metricOptions"
              :disabled="loading"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Operator</label>
            <SelectBox
              v-model="form.operator"
              size="lg"
              wrapper-class="w-full"
              :options="operatorOptions"
              :disabled="loading"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="alert-threshold">
              Threshold
            </label>
            <input
              id="alert-threshold"
              v-model.number="form.threshold"
              type="number"
              step="0.01"
              required
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
              :disabled="loading"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Severity</label>
            <SelectBox
              v-model="form.severity"
              size="lg"
              wrapper-class="w-full"
              :options="severityOptions"
              :disabled="loading"
            />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
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
            :disabled="loading || !form.name.trim()"
          >
            {{ loading ? 'Creating…' : 'Create alert' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { nextTick, reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const nameInput = ref(null);
const form = reactive({
  name: '',
  metric: 'health_score',
  operator: 'lte',
  threshold: 70,
  severity: 'warning',
});

const metricOptions = [
  { value: 'health_score', label: 'Health score' },
  { value: 'crash_rate', label: 'Crash rate' },
  { value: 'anr_rate', label: 'ANR rate' },
  { value: 'api_error_rate', label: 'API error rate' },
  { value: 'response_time', label: 'Response time' },
  { value: 'memory', label: 'Memory' },
  { value: 'battery', label: 'Battery' },
];

const operatorOptions = [
  { value: 'gte', label: '≥' },
  { value: 'gt', label: '>' },
  { value: 'lte', label: '≤' },
  { value: 'lt', label: '<' },
  { value: 'eq', label: '=' },
];

const severityOptions = [
  { value: 'info', label: 'Info' },
  { value: 'warning', label: 'Warning' },
  { value: 'critical', label: 'Critical' },
];

function resetForm() {
  form.name = '';
  form.metric = 'health_score';
  form.operator = 'lte';
  form.threshold = 70;
  form.severity = 'warning';
}

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return;
    resetForm();
    await nextTick();
    nameInput.value?.focus();
  },
);

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}

function onSubmit() {
  if (!form.name.trim() || props.loading) return;
  emit('submit', {
    name: form.name.trim(),
    metric: form.metric,
    operator: form.operator,
    threshold: form.threshold,
    severity: form.severity,
  });
}
</script>
