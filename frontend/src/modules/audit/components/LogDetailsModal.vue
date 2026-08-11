<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex justify-end bg-slate-900/40"
    @click.self="$emit('close')"
  >
    <aside class="flex h-full w-full max-w-lg flex-col bg-white shadow-xl">
      <div class="flex items-start justify-between gap-3 border-b border-zinc-100 px-6 py-5">
        <div class="min-w-0">
          <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
          <p v-if="subtitle" class="mt-0.5 truncate text-sm text-slate-500">{{ subtitle }}</p>
        </div>
        <button
          type="button"
          class="shrink-0 rounded-[12px] border border-zinc-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>
      <div class="flex-1 overflow-y-auto p-6">
        <pre
          class="overflow-x-auto rounded-[12px] bg-slate-950 p-4 text-xs leading-relaxed text-slate-100"
        >{{ pretty }}</pre>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  item: { type: Object, default: null },
  title: { type: String, default: 'Log details' },
  subtitle: { type: String, default: '' },
});
defineEmits(['close']);

const pretty = computed(() => JSON.stringify(props.item || {}, null, 2));
</script>
