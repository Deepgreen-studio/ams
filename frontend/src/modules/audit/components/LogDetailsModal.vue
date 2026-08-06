<template>
  <div v-if="open" class="fixed inset-0 z-50 flex justify-end bg-slate-900/40" @click.self="$emit('close')">
    <aside class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-xl">
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
          <p class="text-sm text-slate-500">{{ subtitle }}</p>
        </div>
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" @click="$emit('close')">Close</button>
      </div>
      <pre class="overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">{{ pretty }}</pre>
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
