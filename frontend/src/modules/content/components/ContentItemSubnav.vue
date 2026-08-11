<template>
  <div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex flex-wrap gap-x-0.5 overflow-x-auto" aria-label="Content item sections">
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="item.to"
        class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
        :class="
          isActive(item.name)
            ? 'border-brand-600 text-brand-700'
            : 'border-transparent text-slate-500 hover:border-zinc-300 hover:text-slate-800'
        "
      >
        {{ item.label }}
      </RouterLink>
    </nav>
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
  {
    name: 'content.show',
    label: 'Details',
    to: { name: 'content.show', params: { id: props.contentId } },
  },
  {
    name: 'content.edit',
    label: 'Edit',
    to: { name: 'content.edit', params: { id: props.contentId } },
  },
  {
    name: 'content.review',
    label: 'Review',
    to: { name: 'content.review', params: { id: props.contentId } },
  },
  {
    name: 'content.versions',
    label: 'Version history',
    to: { name: 'content.versions', params: { id: props.contentId } },
  },
  {
    name: 'content.compare',
    label: 'Compare',
    to: { name: 'content.compare', params: { id: props.contentId } },
  },
]);

function isActive(name) {
  return route.name === name;
}
</script>
