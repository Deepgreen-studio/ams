<template>
  <form class="space-y-6" @submit.prevent="$emit('submit', model)">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>
    <div
      v-if="success"
      class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ success }}
    </div>

    <div class="grid gap-5 md:grid-cols-2">
      <div
        v-for="field in fields"
        :key="field.key"
        :class="field.full ? 'md:col-span-2' : ''"
      >
        <label class="mb-1.5 block text-sm font-medium text-slate-700">
          {{ field.label }}
        </label>
        <select
          v-if="field.type === 'boolean'"
          v-model="model[field.key]"
          class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        >
          <option :value="true">Enabled</option>
          <option :value="false">Disabled</option>
        </select>
        <select
          v-else-if="field.options?.length"
          v-model="model[field.key]"
          class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        >
          <option
            v-for="option in field.options"
            :key="String(option.value)"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <input
          v-else
          v-model="model[field.key]"
          :type="field.type || 'text'"
          class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="{
            'border-rose-400 focus:border-rose-500': Boolean(errors[field.key]),
          }"
          :placeholder="field.placeholder || ''"
        />
        <p v-if="errors[field.key]" class="mt-1.5 text-xs text-rose-600">
          {{ errors[field.key][0] }}
        </p>
        <p v-else-if="field.hint" class="mt-1.5 text-xs text-slate-500">
          {{ field.hint }}
        </p>
      </div>
    </div>

    <div class="flex justify-end border-t border-zinc-100 pt-5">
      <button
        type="submit"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving…' : submitLabel }}
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
  submitLabel: { type: String, default: 'Save settings' },
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
