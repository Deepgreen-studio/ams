<template>
  <article
    class="flex flex-col rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
  >
    <div class="flex items-start gap-3">
      <div
        class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] bg-brand-50"
      >
        <FolderIcon class="h-6 w-6 text-brand-500" />
      </div>
      <div class="min-w-0 flex-1">
        <h3 class="truncate text-base font-semibold text-slate-900">{{ folder.name }}</h3>
        <p class="mt-0.5 truncate font-mono text-xs text-slate-500">{{ folder.slug }}</p>
      </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
      <span
        class="inline-flex items-center rounded-lg bg-zinc-50 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-zinc-100"
      >
        {{ fileCount }} {{ fileCount === 1 ? 'file' : 'files' }}
      </span>
      <span
        v-if="fileCount === 0"
        class="inline-flex items-center rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-100"
      >
        Empty
      </span>
    </div>

    <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-4">
      <RouterLink
        :to="{ name: 'settings.media', query: { folder: folder.uuid } }"
        class="rounded-[12px] px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
      >
        Open in Media
      </RouterLink>
      <button
        type="button"
        class="rounded-[12px] px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
        @click="$emit('delete', folder)"
      >
        Delete
      </button>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { FolderIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  folder: {
    type: Object,
    required: true,
  },
});

defineEmits(['delete']);

const fileCount = computed(() => Number(props.folder?.media_count ?? 0));
</script>
