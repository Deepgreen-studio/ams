<template>
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
    <div class="relative min-w-0 flex-1">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
      />
      <input
        :value="modelValue"
        type="search"
        placeholder="Search…"
        class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
        @input="$emit('update:modelValue', $event.target.value)"
        @keyup.enter="$emit('search')"
      />
    </div>
    <button
      type="button"
      class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
      @click="$emit('search')"
    >
      Search
    </button>
  </div>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

defineProps({ modelValue: { type: String, default: '' } });
defineEmits(['update:modelValue', 'search']);
</script>
