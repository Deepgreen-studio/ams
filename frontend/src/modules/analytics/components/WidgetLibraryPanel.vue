<template>
  <div class="flex h-full min-h-0 flex-col rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div class="border-b border-zinc-100 px-5 py-4">
      <h3 class="text-sm font-semibold text-slate-900">Widget library</h3>
      <p class="mt-0.5 text-xs text-slate-500">Drag onto the canvas or click to add.</p>
    </div>
    <div class="scrollbar-light flex-1 space-y-5 overflow-y-auto p-3">
      <div v-for="group in groups" :key="group.key">
        <p class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
          {{ group.label }}
        </p>
        <div class="space-y-2">
          <button
            v-for="widget in widgetsByGroup(group.key)"
            :key="widget.type"
            type="button"
            class="flex w-full cursor-grab items-start gap-3 rounded-[12px] px-3 py-2.5 text-left ring-1 ring-zinc-100 transition hover:bg-brand-50 hover:ring-brand-200 active:cursor-grabbing"
            draggable="true"
            @dragstart="onDragStart($event, widget)"
            @click="emit('add', widget)"
          >
            <span
              class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-zinc-50 text-slate-500"
            >
              <component :is="iconFor(widget.type)" class="h-4 w-4" />
            </span>
            <span class="min-w-0">
              <span class="block text-sm font-medium text-slate-900">{{ widget.label }}</span>
              <span class="mt-0.5 block text-[11px] text-slate-500">{{ widget.description }}</span>
              <span class="mt-1 block text-[10px] uppercase tracking-wide text-slate-400">
                {{ widget.default_width }}×{{ widget.default_height }}
              </span>
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  BellIcon,
  ChartBarIcon,
  ChartPieIcon,
  ClockIcon,
  PresentationChartLineIcon,
  Square3Stack3DIcon,
  TableCellsIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  library: { type: Object, default: null },
});

const emit = defineEmits(['add']);

const groups = computed(() => props.library?.groups || []);
const widgets = computed(() => props.library?.widgets || []);

function widgetsByGroup(key) {
  return widgets.value.filter((item) => item.group === key);
}

function iconFor(type) {
  switch (type) {
    case 'kpi':
    case 'gauge':
      return Square3Stack3DIcon;
    case 'line_chart':
      return PresentationChartLineIcon;
    case 'bar_chart':
      return ChartBarIcon;
    case 'pie_chart':
    case 'heatmap':
      return ChartPieIcon;
    case 'table':
      return TableCellsIcon;
    case 'activity_feed':
      return ClockIcon;
    case 'notifications':
      return BellIcon;
    default:
      return ChartBarIcon;
  }
}

function onDragStart(event, widget) {
  event.dataTransfer.setData('application/x-ams-widget', JSON.stringify(widget));
  event.dataTransfer.effectAllowed = 'copy';
}
</script>
