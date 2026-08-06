<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @click.self="$emit('close')">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-auto rounded-xl bg-white p-6 shadow-xl">
      <div class="mb-4 flex items-start justify-between gap-4">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">{{ item?.original_name }}</h3>
          <p class="text-sm text-slate-500">{{ item?.human_size }} · {{ item?.mime_type }}</p>
        </div>
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" @click="$emit('close')">Close</button>
      </div>
      <img v-if="item && isImage" :src="item.url" alt="" class="max-h-[60vh] w-full rounded-lg object-contain" />
      <div v-else class="rounded-lg bg-slate-100 p-8 text-center text-sm text-slate-600">
        Preview unavailable. <a :href="item?.url" target="_blank" class="font-medium text-brand-700">Download / open</a>
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
