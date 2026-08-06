<template>
  <li>
    <button
      type="button"
      class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm"
      :class="selected === folder.uuid ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-700 hover:bg-slate-50'"
      :style="{ paddingLeft: `${12 + depth * 14}px` }"
      @click="$emit('select', folder)"
    >
      {{ folder.name }}
    </button>
    <ul v-if="folder.children?.length" class="space-y-1">
      <MediaFolderNode
        v-for="child in folder.children"
        :key="child.uuid"
        :folder="child"
        :selected="selected"
        :depth="depth + 1"
        @select="$emit('select', $event)"
      />
    </ul>
  </li>
</template>

<script setup>
defineProps({
  folder: { type: Object, required: true },
  selected: { type: String, default: null },
  depth: { type: Number, default: 0 },
});

defineEmits(['select']);
</script>
