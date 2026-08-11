<template>
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    <EmptyState
      v-if="!loading && !items.length"
      title="No media files"
      description="Upload images, documents, or archives to get started."
    />

    <template v-if="loading">
      <div
        v-for="n in 8"
        :key="`skeleton-${n}`"
        class="h-52 animate-pulse rounded-[12px] bg-zinc-100"
      />
    </template>

    <template v-else>
      <article
        v-for="item in items"
        :key="item.uuid"
        class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <button type="button" class="block w-full" @click="$emit('preview', item)">
          <div class="flex h-36 items-center justify-center bg-zinc-50">
            <img
              v-if="isImage(item)"
              :src="item.url"
              :alt="item.original_name"
              class="h-full w-full object-cover"
            />
            <span v-else class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ item.extension }}
            </span>
          </div>
        </button>
        <div class="space-y-2 p-3.5">
          <p class="truncate text-sm font-medium text-slate-900">{{ item.original_name }}</p>
          <p class="text-xs text-slate-500">{{ item.human_size }}</p>
          <div class="flex justify-between gap-2">
            <a
              :href="item.url"
              target="_blank"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Open
            </a>
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
    </template>
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
