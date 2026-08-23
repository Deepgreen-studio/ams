<template>
  <div class="relative">
    <input
      :id="id"
      :type="visible ? 'text' : 'password'"
      :value="modelValue"
      :autocomplete="autocomplete"
      :required="required"
      :disabled="disabled"
      class="h-11 w-full rounded-xl bg-white px-3.5 pr-11 text-sm text-zinc-900 outline-none ring-1 ring-zinc-200 transition placeholder:text-zinc-400 focus:ring-brand-500 disabled:bg-zinc-50"
      :placeholder="placeholder"
      :class="inputClass"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <button
      type="button"
      class="absolute inset-y-0 right-0 px-3 text-zinc-400 hover:text-zinc-700"
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
  inputClass: { type: [String, Object, Array], default: '' },
  placeholder: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const visible = ref(false);
</script>
