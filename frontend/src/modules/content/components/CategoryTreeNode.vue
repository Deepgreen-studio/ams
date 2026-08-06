<template>
  <li>
    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2" :style="{ marginLeft: `${depth * 1.25}rem` }">
      <div>
        <p class="text-sm font-medium text-slate-900">{{ node.name }}</p>
        <p class="text-xs text-slate-500">{{ node.slug }} · {{ node.is_active ? 'Active' : 'Inactive' }} · {{ node.contents_count || 0 }} items</p>
      </div>
      <div class="flex gap-2">
        <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100" @click="$emit('edit', node)">Edit</button>
        <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50" @click="$emit('delete', node)">Delete</button>
      </div>
    </div>
    <ul v-if="node.children?.length" class="mt-1 space-y-1">
      <CategoryTreeNode
        v-for="child in node.children"
        :key="child.uuid"
        :node="child"
        :depth="depth + 1"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      />
    </ul>
  </li>
</template>

<script setup>
import CategoryTreeNode from '@/modules/content/components/CategoryTreeNode.vue';

defineProps({
  node: { type: Object, required: true },
  depth: { type: Number, default: 0 },
});
defineEmits(['edit', 'delete']);
</script>
