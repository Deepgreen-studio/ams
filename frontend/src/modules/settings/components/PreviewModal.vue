<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
    @click.self="$emit('close')"
  >
    <div
      class="max-h-[90vh] w-full max-w-2xl overflow-auto rounded-[12px] bg-white p-6 shadow-xl ring-1 ring-zinc-100"
    >
      <div class="mb-4 flex items-start justify-between gap-4">
        <div class="min-w-0">
          <h3 class="truncate text-lg font-semibold text-slate-900">
            {{ item?.original_name }}
          </h3>
          <p class="text-sm text-slate-500">{{ item?.human_size }} · {{ item?.mime_type }}</p>
        </div>
        <button
          type="button"
          class="shrink-0 rounded-[12px] border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>
      <img
        v-if="item && isImage"
        :src="item.url"
        alt=""
        class="max-h-[60vh] w-full rounded-[12px] object-contain"
      />
      <div
        v-else
        class="rounded-[12px] bg-zinc-50 p-8 text-center text-sm text-slate-600"
      >
        Preview unavailable.
        <a :href="item?.url" target="_blank" class="font-medium text-brand-700">
          Download / open
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  item: { type: Object, default: null },
});
defineEmits(['close']);

const isImage = computed(() => (props.item?.mime_type || '').startsWith('image/'));
</script>
