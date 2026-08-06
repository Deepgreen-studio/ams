<template>
  <div>
    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!items.length"
      title="No media found"
      description="Upload files or adjust filters to see assets."
    />
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <article
        v-for="item in items"
        :key="item.uuid"
        class="overflow-hidden rounded-xl border border-slate-200 bg-white"
      >
        <button type="button" class="block w-full bg-slate-50" @click="$emit('preview', item)">
          <img
            v-if="item.is_image"
            :src="item.url"
            :alt="item.alt_text || item.name"
            class="h-36 w-full object-cover"
          />
          <div
            v-else
            class="flex h-36 items-center justify-center text-sm font-medium uppercase text-slate-500"
          >
            {{ item.extension }}
          </div>
        </button>
        <div class="space-y-2 p-3">
          <p class="truncate text-sm font-medium text-slate-900" :title="item.original_name">
            {{ item.original_name }}
          </p>
          <p class="text-xs text-slate-500">
            {{ item.type }} · {{ item.human_size }} · v{{ item.version }}
          </p>
          <div class="flex flex-wrap gap-1">
            <button
              type="button"
              class="rounded px-2 py-1 text-xs text-brand-700 hover:bg-brand-50"
              @click="$emit('preview', item)"
            >
              Preview
            </button>
            <button
              type="button"
              class="rounded px-2 py-1 text-xs text-slate-700 hover:bg-slate-100"
              @click="$emit('download', item)"
            >
              Download
            </button>
            <button
              type="button"
              class="rounded px-2 py-1 text-xs text-slate-700 hover:bg-slate-100"
              @click="$emit('replace', item)"
            >
              Replace
            </button>
            <button
              type="button"
              class="rounded px-2 py-1 text-xs text-slate-700 hover:bg-slate-100"
              @click="$emit('versions', item)"
            >
              History
            </button>
            <button
              type="button"
              class="rounded px-2 py-1 text-xs text-rose-700 hover:bg-rose-50"
              @click="$emit('delete', item)"
            >
              Delete
            </button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['preview', 'download', 'replace', 'versions', 'delete']);
</script>
