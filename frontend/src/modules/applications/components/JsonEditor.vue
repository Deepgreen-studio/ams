<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-2">
      <label class="text-sm font-medium text-slate-700">{{ label }}</label>
      <button
        type="button"
        class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-50"
        :disabled="validating || !modelValue"
        @click="$emit('validate')"
      >
        {{ validating ? 'Validating...' : 'Validate JSON' }}
      </button>
    </div>
    <textarea
      :value="modelValue"
      rows="16"
      class="w-full h-12 rounded-[12px] border border-slate-300 px-3 font-mono text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      :class="error ? 'border-rose-400' : ''"
      spellcheck="false"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
    <p v-if="error" class="text-xs text-rose-600">{{ error }}</p>
    <div v-if="validation" class="rounded-lg border px-3 py-2 text-xs" :class="validation.valid ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'">
      <p class="font-medium">{{ validation.valid ? 'Payload is valid' : 'Payload validation failed' }}</p>
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
