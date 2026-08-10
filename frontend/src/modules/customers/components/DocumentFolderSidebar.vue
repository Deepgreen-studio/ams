<template>
  <aside class="rounded-[12px] bg-white p-3 ring-1 ring-zinc-100">
    <p class="px-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Folders</p>
    <nav class="mt-2 space-y-1">
      <button
        type="button"
        class="flex w-full items-center justify-between rounded-[12px] px-3 py-2.5 text-sm transition"
        :class="
          !modelValue
            ? 'bg-brand-50 font-medium text-brand-700'
            : 'text-slate-700 hover:bg-zinc-50'
        "
        @click="$emit('update:modelValue', '')"
      >
        <span>All documents</span>
        <span class="text-xs text-slate-500">{{ totalCount }}</span>
      </button>
      <button
        v-for="folder in folders"
        :key="folder.category"
        type="button"
        class="flex w-full items-center justify-between rounded-[12px] px-3 py-2.5 text-sm transition"
        :class="
          modelValue === folder.category
            ? 'bg-brand-50 font-medium text-brand-700'
            : 'text-slate-700 hover:bg-zinc-50'
        "
        @click="$emit('update:modelValue', folder.category)"
      >
        <span>{{ folder.label }}</span>
        <span class="text-xs text-slate-500">{{ folder.count }}</span>
      </button>
    </nav>
  </aside>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  folders: { type: Array, default: () => [] },
  modelValue: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const totalCount = computed(() =>
  props.folders.reduce((sum, folder) => sum + (folder.count || 0), 0),
);
</script>
