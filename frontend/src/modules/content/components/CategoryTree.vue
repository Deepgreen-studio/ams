<template>
  <div class="space-y-2">
    <div v-if="loading" class="space-y-2">
      <div v-for="n in 4" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <p v-else-if="!nodes.length" class="text-sm text-slate-500">No categories in the tree yet.</p>
    <ul v-else class="space-y-1">
      <CategoryTreeNode
        v-for="node in nodes"
        :key="node.uuid"
        :node="node"
        :depth="0"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      />
    </ul>
  </div>
</template>

<script setup>
import CategoryTreeNode from '@/modules/content/components/CategoryTreeNode.vue';

defineProps({
  nodes: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['edit', 'delete']);
</script>
