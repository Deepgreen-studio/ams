<template>
  <div class="flex h-full min-h-0 flex-col rounded-xl border border-slate-200 bg-white p-3">
    <div class="mb-2 flex items-start justify-between gap-2">
      <div class="min-w-0">
        <h3 class="truncate text-sm font-semibold text-slate-900">{{ widget.name }}</h3>
        <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ widget.type?.replaceAll('_', ' ') }}</p>
      </div>
      <slot name="actions" />
    </div>

    <div class="min-h-0 flex-1 overflow-auto">
      <template v-if="widget.type === 'kpi' || widget.type === 'gauge'">
        <p class="text-3xl font-semibold text-slate-900">{{ formatNumber(widget.data?.value) }}</p>
        <p class="mt-1 text-xs text-slate-500">Events in selected period</p>
      </template>

      <template v-else-if="widget.type === 'line_chart'">
        <SimpleLineChart
          title=""
          :labels="widget.data?.labels || []"
          :series="[{ key: 'count', label: 'Events', values: widget.data?.values || [] }]"
        />
      </template>

      <template v-else-if="widget.type === 'bar_chart' || widget.type === 'pie_chart' || widget.type === 'heatmap'">
        <SimpleBarChart title="" :data="widget.data?.by_category || widget.data?.series || {}" />
      </template>

      <template v-else-if="widget.type === 'table'">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-2 py-2 font-medium">Event</th>
              <th class="px-2 py-2 font-medium">Count</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in widget.data?.rows || []" :key="row.event_name" class="border-b border-slate-100">
              <td class="px-2 py-2 text-slate-700">{{ row.event_name }}</td>
              <td class="px-2 py-2 font-medium text-slate-900">{{ formatNumber(row.count) }}</td>
            </tr>
            <tr v-if="!(widget.data?.rows || []).length">
              <td colspan="2" class="px-2 py-6 text-center text-slate-500">No rows</td>
            </tr>
          </tbody>
        </table>
      </template>

      <template v-else-if="widget.type === 'map'">
        <div class="grid grid-cols-2 gap-2">
          <div
            v-for="region in widget.data?.regions || []"
            :key="region.code"
            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
          >
            <p class="text-xs font-medium text-slate-500">{{ region.code }}</p>
            <p class="text-sm font-semibold text-slate-900">{{ region.label }}</p>
            <p class="text-lg font-semibold text-brand-700">{{ formatNumber(region.value) }}</p>
          </div>
        </div>
      </template>

      <template v-else-if="widget.type === 'activity_feed'">
        <ul class="space-y-2">
          <li
            v-for="item in widget.data?.items || []"
            :key="item.id"
            class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2"
          >
            <p class="text-sm text-slate-800">{{ item.description }}</p>
            <p class="mt-1 text-[11px] text-slate-500">
              {{ item.log_name || 'activity' }} · {{ formatDate(item.created_at) }}
            </p>
          </li>
          <li v-if="!(widget.data?.items || []).length" class="py-6 text-center text-sm text-slate-500">
            No recent activity
          </li>
        </ul>
      </template>

      <template v-else-if="widget.type === 'notifications'">
        <ul class="space-y-2">
          <li
            v-for="item in widget.data?.items || []"
            :key="item.uuid"
            class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2"
          >
            <p class="text-sm font-medium text-slate-800">{{ item.event_name }}</p>
            <p class="mt-1 text-[11px] text-slate-500">
              {{ item.event_source || 'notifications' }} · {{ formatDate(item.occurred_at) }}
            </p>
          </li>
          <li v-if="!(widget.data?.items || []).length" class="py-6 text-center text-sm text-slate-500">
            No notification events
          </li>
        </ul>
      </template>

      <template v-else>
        <SimpleBarChart title="" :data="widget.data?.by_category || {}" />
      </template>
    </div>
  </div>
</template>

<script setup>
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import SimpleBarChart from '@/modules/compliance/components/SimpleBarChart.vue';

defineProps({
  widget: { type: Object, required: true },
});

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
