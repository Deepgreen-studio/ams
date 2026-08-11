<template>
  <div class="space-y-2">
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!nodes.length"
      title="No categories yet"
      description="Create categories from the category list to build a nested hierarchy."
      class="px-4 py-10"
    />

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
import EmptyState from '@/components/ui/EmptyState.vue';
import CategoryTreeNode from '@/modules/content/components/CategoryTreeNode.vue';

defineProps({
  nodes: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['edit', 'delete']);
</script>
