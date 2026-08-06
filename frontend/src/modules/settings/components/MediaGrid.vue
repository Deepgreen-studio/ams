<template>
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    <EmptyState
      v-if="!loading && !items.length"
      title="No media files"
      description="Upload images, documents, or archives to get started."
    />
    <article
      v-for="item in items"
      :key="item.uuid"
      class="overflow-hidden rounded-xl border border-slate-200 bg-white"
    >
      <button type="button" class="block w-full" @click="$emit('preview', item)">
        <div class="flex h-36 items-center justify-center bg-slate-100">
          <img
            v-if="isImage(item)"
            :src="item.url"
            :alt="item.original_name"
            class="h-full w-full object-cover"
          />
          <span v-else class="text-xs font-medium uppercase text-slate-500">{{
            item.extension
          }}</span>
        </div>
      </button>
      <div class="space-y-2 p-3">
        <p class="truncate text-sm font-medium text-slate-900">{{ item.original_name }}</p>
        <p class="text-xs text-slate-500">{{ item.human_size }}</p>
        <div class="flex justify-between gap-2">
          <a
            :href="item.url"
            target="_blank"
            class="text-xs font-medium text-brand-700 hover:underline"
            >Open</a
          >
          <button
            type="button"
            class="text-xs font-medium text-rose-700 hover:underline"
            @click="$emit('delete', item)"
          >
            Delete
          </button>
        </div>
      </div>
    </article>
  </div>
</template>

<script setup>
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['preview', 'delete']);

function isImage(item) {
  return (item.mime_type || '').startsWith('image/');
}
</script>
