<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-2">
      <label class="text-sm font-medium text-slate-700">{{ label }}</label>
      <button
        type="button"
        class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50 disabled:opacity-50"
        :disabled="validating || !modelValue"
        @click="$emit('validate')"
      >
        {{ validating ? 'Validating...' : 'Validate JSON' }}
      </button>
    </div>
    <textarea
      :value="modelValue"
      rows="16"
      class="min-h-[18rem] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 font-mono text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
      :class="error ? 'border-rose-400 focus:border-rose-500' : ''"
      spellcheck="false"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
    <p v-if="error" class="text-xs text-rose-600">{{ error }}</p>
    <div
      v-if="validation"
      class="rounded-[12px] px-3.5 py-2.5 text-xs ring-1"
      :class="
        validation.valid
          ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
          : 'bg-rose-50 text-rose-700 ring-rose-100'
      "
    >
      <p class="font-medium">
        {{ validation.valid ? 'Payload is valid' : 'Payload validation failed' }}
      </p>
      <ul v-if="!validation.valid && validation.errors" class="mt-1 list-disc pl-4">
        <li v-for="(messages, field) in validation.errors" :key="field">
          {{ field }}: {{ Array.isArray(messages) ? messages.join(', ') : messages }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: 'JSON payload' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  validating: { type: Boolean, default: false },
  validation: { type: Object, default: null },
});

defineEmits(['update:modelValue', 'validate']);
</script>
