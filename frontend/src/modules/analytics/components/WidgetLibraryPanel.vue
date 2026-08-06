<template>
  <div class="flex h-full min-h-0 flex-col rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-4 py-3">
      <h3 class="text-sm font-semibold text-slate-900">Widget Library</h3>
      <p class="text-xs text-slate-500">Drag a widget onto the canvas or click to add.</p>
    </div>
    <div class="flex-1 space-y-4 overflow-y-auto p-3">
      <div v-for="group in groups" :key="group.key">
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ group.label }}</p>
        <div class="space-y-2">
          <button
            v-for="widget in widgetsByGroup(group.key)"
            :key="widget.type"
            type="button"
            class="flex w-full cursor-grab flex-col rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left hover:border-brand-300 hover:bg-brand-50 active:cursor-grabbing"
            draggable="true"
            @dragstart="onDragStart($event, widget)"
            @click="emit('add', widget)"
          >
            <span class="text-sm font-medium text-slate-900">{{ widget.label }}</span>
            <span class="mt-0.5 text-[11px] text-slate-500">{{ widget.description }}</span>
            <span class="mt-1 text-[10px] uppercase tracking-wide text-slate-400">
              {{ widget.default_width }}×{{ widget.default_height }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  library: { type: Object, default: null },
});

const emit = defineEmits(['add']);

const groups = computed(() => props.library?.groups || []);
const widgets = computed(() => props.library?.widgets || []);

function widgetsByGroup(key) {
  return widgets.value.filter((item) => item.group === key);
}

function onDragStart(event, widget) {
  event.dataTransfer.setData('application/x-ams-widget', JSON.stringify(widget));
  event.dataTransfer.effectAllowed = 'copy';
}
</script>
