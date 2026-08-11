<template>
  <div class="rounded-[12px] bg-white p-4 ring-1 ring-zinc-100 sm:p-5">
    <div class="mb-3 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-slate-900">Folders</h3>
      <button
        type="button"
        class="rounded-lg px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
        @click="$emit('create')"
      >
        New
      </button>
    </div>
    <button
      type="button"
      class="mb-1 w-full rounded-[10px] px-3 py-2 text-left text-sm transition"
      :class="!selected ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-zinc-50'"
      @click="$emit('select', null)"
    >
      All files
    </button>
    <button
      v-for="folder in folders"
      :key="folder.uuid"
      type="button"
      class="mb-1 flex w-full items-center justify-between rounded-[10px] px-3 py-2 text-left text-sm transition"
      :class="
        selected === folder.uuid ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-zinc-50'
      "
      @click="$emit('select', folder)"
    >
      <span class="truncate">{{ folder.name }}</span>
      <span class="text-xs text-slate-400">{{ folder.media_count ?? '' }}</span>
    </button>
    <EmptyState
      v-if="!folders.length"
      title="No folders"
      description="Create a folder to organize files."
    />
  </div>
</template>

<script setup>
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  folders: { type: Array, default: () => [] },
  selected: { type: String, default: null },
});
defineEmits(['select', 'create']);
</script>
