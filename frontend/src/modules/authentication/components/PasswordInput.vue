<template>
  <div class="relative">
    <input
      :id="id"
      :type="visible ? 'text' : 'password'"
      :value="modelValue"
      :autocomplete="autocomplete"
      :required="required"
      :disabled="disabled"
      class="w-full h-12 rounded-[12px] border border-slate-300 px-3 pr-10 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 disabled:bg-slate-50"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <button
      type="button"
      class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-slate-700"
      :aria-label="visible ? 'Hide password' : 'Show password'"
      @click="visible = !visible"
    >
      <EyeSlashIcon v-if="visible" class="h-4 w-4" />
      <EyeIcon v-else class="h-4 w-4" />
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

defineProps({
  id: { type: String, required: true },
  modelValue: { type: String, default: '' },
  autocomplete: { type: String, default: 'current-password' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const visible = ref(false);
</script>
