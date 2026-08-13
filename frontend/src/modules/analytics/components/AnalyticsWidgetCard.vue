<template>
  <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-[12px] bg-white p-4 ring-1 ring-zinc-100">
    <div class="mb-3 flex items-start justify-between gap-2">
      <div class="min-w-0">
        <h3 class="truncate text-sm font-semibold text-slate-900">{{ widget.name }}</h3>
        <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-400">
          {{ typeLabel }}
        </p>
      </div>
      <slot name="actions" />
    </div>

    <div class="scrollbar-light min-h-0 flex-1 overflow-auto">
      <template v-if="widget.type === 'kpi' || widget.type === 'gauge'">
        <div class="flex h-full items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate text-3xl font-bold tracking-tight text-slate-900">
              {{ formatNumber(widget.data?.value) }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Events in selected period</p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-brand-50">
            <ChartBarIcon class="h-5 w-5 text-brand-500" />
          </div>
        </div>
      </template>

      <template v-else-if="widget.type === 'line_chart'">
        <SimpleLineChart
          title=""
          :framed="false"
          :labels="widget.data?.labels || []"
          :series="[{ key: 'count', label: 'Events', values: widget.data?.values || [] }]"
        />
      </template>

      <template v-else-if="widget.type === 'bar_chart' || widget.type === 'pie_chart' || widget.type === 'heatmap'">
        <SimpleBarChart
          title=""
          :framed="false"
          :data="widget.data?.by_category || widget.data?.series || {}"
        />
      </template>

      <template v-else-if="widget.type === 'table'">
        <table class="min-w-full text-left text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="py-2 pr-3 font-semibold text-zinc-500">Event</th>
              <th class="py-2 font-semibold text-zinc-500">Count</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in widget.data?.rows || []"
              :key="row.event_name"
              class="border-b border-zinc-50 last:border-0"
            >
              <td class="py-2.5 pr-3 text-slate-700">{{ row.event_name }}</td>
              <td class="py-2.5 font-medium text-slate-900">{{ formatNumber(row.count) }}</td>
            </tr>
            <tr v-if="!(widget.data?.rows || []).length">
              <td colspan="2" class="py-8 text-center text-sm text-slate-500">No rows</td>
            </tr>
          </tbody>
        </table>
      </template>

      <template v-else-if="widget.type === 'map'">
        <div class="grid grid-cols-2 gap-2">
          <div
            v-for="region in widget.data?.regions || []"
            :key="region.code"
            class="rounded-[12px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100"
          >
            <p class="text-xs font-medium text-slate-500">{{ region.code }}</p>
            <p class="truncate text-sm font-semibold text-slate-900">{{ region.label }}</p>
            <p class="text-lg font-bold tracking-tight text-brand-700">{{ formatNumber(region.value) }}</p>
          </div>
        </div>
      </template>

      <template v-else-if="widget.type === 'activity_feed'">
        <ul v-if="widget.data?.items?.length" class="space-y-2">
          <li
            v-for="item in widget.data.items"
            :key="item.id"
            class="rounded-[12px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100"
          >
            <p class="text-sm text-slate-800">{{ item.description }}</p>
            <p class="mt-1 text-[11px] text-slate-500">
              {{ item.log_name || 'activity' }} · {{ formatDate(item.created_at) }}
            </p>
          </li>
        </ul>
        <p v-else class="py-8 text-center text-sm text-slate-500">No recent activity</p>
      </template>

      <template v-else-if="widget.type === 'notifications'">
        <ul v-if="widget.data?.items?.length" class="space-y-2">
          <li
            v-for="item in widget.data.items"
            :key="item.uuid"
            class="rounded-[12px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100"
          >
            <p class="text-sm font-medium text-slate-800">{{ item.event_name }}</p>
            <p class="mt-1 text-[11px] text-slate-500">
              {{ item.event_source || 'notifications' }} · {{ formatDate(item.occurred_at) }}
            </p>
          </li>
        </ul>
        <p v-else class="py-8 text-center text-sm text-slate-500">No notification events</p>
      </template>

      <template v-else>
        <SimpleBarChart title="" :framed="false" :data="widget.data?.by_category || {}" />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChartBarIcon } from '@heroicons/vue/24/outline';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';

const props = defineProps({
  widget: { type: Object, required: true },
});

const typeLabel = computed(() => String(props.widget.type || '').replaceAll('_', ' '));

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
