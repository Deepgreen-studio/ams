<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-1.5 text-sm font-medium"
      :class="isActive(item.name) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100'"
    >
      {{ item.label }}
    </RouterLink>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const props = defineProps({
  contentId: {
    type: [String, Number],
    required: true,
  },
});

const route = useRoute();

const items = computed(() => [
  { name: 'content.show', label: 'Details', to: { name: 'content.show', params: { id: props.contentId } } },
  { name: 'content.edit', label: 'Edit', to: { name: 'content.edit', params: { id: props.contentId } } },
  { name: 'content.review', label: 'Review', to: { name: 'content.review', params: { id: props.contentId } } },
  { name: 'content.versions', label: 'Version history', to: { name: 'content.versions', params: { id: props.contentId } } },
  { name: 'content.compare', label: 'Compare', to: { name: 'content.compare', params: { id: props.contentId } } },
]);

function isActive(name) {
  return route.name === name;
}
</script>
