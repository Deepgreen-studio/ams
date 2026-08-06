<template>
  <aside class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="mb-3 flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-slate-900">Folders</h3>
      <button
        type="button"
        class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
        @click="$emit('create')"
      >
        New
      </button>
    </div>
    <button
      type="button"
      class="mb-1 flex w-full items-center rounded-lg px-3 py-2 text-left text-sm"
      :class="
        !selected ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-700 hover:bg-slate-50'
      "
      @click="$emit('select', null)"
    >
      All media (root)
    </button>
    <ul class="space-y-1">
      <MediaFolderNode
        v-for="folder in folders"
        :key="folder.uuid"
        :folder="folder"
        :selected="selected"
        :depth="0"
        @select="$emit('select', $event)"
      />
    </ul>
  </aside>
</template>

<script setup>
import MediaFolderNode from '@/modules/content/components/media/MediaFolderNode.vue';

defineProps({
  folders: { type: Array, default: () => [] },
  selected: { type: String, default: null },
});

defineEmits(['select', 'create']);
</script>
