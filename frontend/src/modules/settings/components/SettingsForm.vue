<template>
  <form class="space-y-4" @submit.prevent="$emit('submit', model)">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div v-if="success" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ success }}</div>
    <div class="grid gap-4 md:grid-cols-2">
      <div v-for="field in fields" :key="field.key" :class="field.full ? 'md:col-span-2' : ''">
        <label class="mb-1 block text-sm font-medium text-slate-700">{{ field.label }}</label>
        <select
          v-if="field.type === 'boolean'"
          v-model="model[field.key]"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option :value="true">Enabled</option>
          <option :value="false">Disabled</option>
        </select>
        <input
          v-else
          v-model="model[field.key]"
          :type="field.type || 'text'"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          :placeholder="field.placeholder || ''"
        />
        <p v-if="errors[field.key]" class="mt-1 text-xs text-rose-600">{{ errors[field.key][0] }}</p>
      </div>
    </div>
    <div class="flex justify-end">
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="loading">
        {{ loading ? 'Saving...' : 'Save settings' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  fields: { type: Array, default: () => [] },
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  success: { type: String, default: '' },
  loading: { type: Boolean, default: false },
});

defineEmits(['submit']);

const model = reactive({});

watch(
  () => props.initial,
  (value) => {
    props.fields.forEach((field) => {
      model[field.key] = value?.[field.key] ?? (field.type === 'boolean' ? false : '');
    });
  },
  { immediate: true, deep: true },
);
</script>
