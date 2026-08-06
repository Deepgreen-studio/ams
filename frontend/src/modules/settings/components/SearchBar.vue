<template>
  <div class="flex flex-col gap-3 sm:flex-row">
    <input
      :value="modelValue"
      type="search"
      placeholder="Search..."
      class="w-full flex-1 h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
      @input="$emit('update:modelValue', $event.target.value)"
      @keyup.enter="$emit('search')"
    />
    <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700" @click="$emit('search')">
      Search
    </button>
  </div>
</template>

<script setup>
defineProps({ modelValue: { type: String, default: '' } });
defineEmits(['update:modelValue', 'search']);
</script>
