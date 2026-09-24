<template>
  <form class="space-y-6" @submit.prevent="submitForm">
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
        <SelectBox
          v-if="field.type === 'boolean'"
          v-model="model[field.key]"
          size="lg"
          :options="booleanOptions"
          :error="Boolean(errors[field.key])"
        />
        <SearchableSelect
          v-else-if="field.searchable"
          v-model="model[field.key]"
          :options="optionsFor(field)"
          :placeholder="field.placeholder || 'Select…'"
          :search-placeholder="field.searchPlaceholder || 'Search…'"
          :button-class="searchableButtonClass(field)"
        />
        <SelectBox
          v-else-if="field.options?.length"
          v-model="model[field.key]"
          size="lg"
          :options="optionsFor(field)"
          :placeholder="field.placeholder || ''"
          :error="Boolean(errors[field.key])"
        />
        <div
          v-else-if="field.type === 'image'"
          class="flex h-12 w-full items-center gap-2 rounded-xl border bg-white pl-2 pr-1.5"
          :class="errors[field.key] ? 'border-rose-400' : 'border-slate-200'"
        >
          <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100">
            <img
              v-if="imageSrc(field)"
              :src="imageSrc(field)"
              alt="Application logo"
              class="h-full w-full object-contain"
            />
            <PhotoIcon v-else class="h-4 w-4 text-slate-500" />
          </span>
          <span
            class="min-w-0 flex-1 truncate text-sm"
            :class="imageLabel(field) ? 'text-slate-800' : 'text-slate-500'"
          >
            {{ imageLabel(field) || 'No logo selected' }}
          </span>
          <button
            v-if="canRemoveImage(field)"
            type="button"
            class="shrink-0 px-2 text-xs font-medium text-rose-600 hover:text-rose-700 disabled:opacity-60"
            :disabled="loading"
            @click="clearImage(field)"
          >
            Remove
          </button>
          <label
            class="inline-flex h-8 shrink-0 cursor-pointer items-center rounded-lg bg-brand-600 px-3 text-xs font-medium text-white hover:bg-brand-700"
            :class="loading ? 'pointer-events-none opacity-60' : ''"
          >
            Choose
            <input
              type="file"
              class="sr-only"
              :accept="field.accept || 'image/png,image/jpeg,image/webp'"
              :disabled="loading"
              @change="onImageSelected(field, $event)"
            />
          </label>
        </div>
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
import { PhotoIcon } from '@heroicons/vue/24/outline';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { resolveMediaUrl } from '@/utils/mediaUrl';

const booleanOptions = [
  { value: true, label: 'Enabled' },
  { value: false, label: 'Disabled' },
];

const props = defineProps({
  fields: { type: Array, default: () => [] },
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  success: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save settings' },
});

const emit = defineEmits(['submit']);

const model = reactive({});
const imagePreview = reactive({});
const imageNames = reactive({});
const pendingFiles = reactive({});
const removedImages = reactive({});

watch(
  () => props.initial,
  (value) => {
    props.fields.forEach((field) => {
      model[field.key] = value?.[field.key] ?? (field.type === 'boolean' ? false : '');
      if (field.type === 'image') {
        resetImageSelection(field.key);
      }
    });
  },
  { immediate: true, deep: true },
);

function optionsFor(field) {
  const options = field.options || [];
  const current = model[field.key];

  if (
    current !== '' &&
    current !== null &&
    current !== undefined &&
    !options.some((option) => option.value === current)
  ) {
    return [{ value: current, label: String(current) }, ...options];
  }

  return options;
}

function searchableButtonClass(field) {
  const base =
    'h-12 w-full rounded-xl border bg-white px-3.5 text-sm shadow-none focus:outline-none focus:ring-0';

  if (props.errors[field.key]) {
    return `${base} border-rose-400 text-slate-900 focus:border-rose-500`;
  }

  return `${base} border-slate-200 text-slate-900 focus:border-brand-500`;
}

function imageSrc(field) {
  if (imagePreview[field.key]) {
    return imagePreview[field.key];
  }

  if (removedImages[field.key]) {
    return '';
  }

  return resolveMediaUrl(model[field.key]);
}

function imageLabel(field) {
  if (imageNames[field.key]) {
    return imageNames[field.key];
  }

  if (removedImages[field.key] || !model[field.key]) {
    return '';
  }

  return 'Current logo';
}

function resetImageSelection(key) {
  if (imagePreview[key]) {
    URL.revokeObjectURL(imagePreview[key]);
  }

  imagePreview[key] = '';
  imageNames[key] = '';
  pendingFiles[key] = null;
  removedImages[key] = false;
}

function canRemoveImage(field) {
  return Boolean(pendingFiles[field.key] || (model[field.key] && !removedImages[field.key]));
}

function onImageSelected(field, event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file) {
    return;
  }

  if (imagePreview[field.key]) {
    URL.revokeObjectURL(imagePreview[field.key]);
  }

  imagePreview[field.key] = URL.createObjectURL(file);
  imageNames[field.key] = file.name;
  pendingFiles[field.key] = file;
  removedImages[field.key] = false;
}

function clearImage(field) {
  if (pendingFiles[field.key]) {
    resetImageSelection(field.key);
    return;
  }

  if (model[field.key]) {
    removedImages[field.key] = true;
  }
}

function submitForm() {
  const payload = { ...model };
  const images = {};
  const removed = [];

  props.fields.forEach((field) => {
    if (field.type !== 'image') {
      return;
    }

    delete payload[field.key];

    if (pendingFiles[field.key]) {
      images[field.key] = pendingFiles[field.key];
      return;
    }

    if (removedImages[field.key]) {
      removed.push(field.key);
    }
  });

  emit('submit', payload, { images, removed });
}

watch(
  () => props.errors,
  (errors) => {
    props.fields.forEach((field) => {
      if (field.type === 'image' && errors?.[field.key]) {
        resetImageSelection(field.key);
      }
    });
  },
  { deep: true },
);
</script>
