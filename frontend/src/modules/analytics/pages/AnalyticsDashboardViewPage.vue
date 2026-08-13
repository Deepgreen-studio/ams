<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.dashboards' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ArrowLeftIcon class="h-4 w-4" />
        Back
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
        :disabled="store.saving || !dashboardUuid"
        @click="showWidgetForm = true"
      >
        <PlusIcon class="h-4 w-4" />
        Add widget
      </button>
      <RouterLink
        :to="{ name: 'analytics.dashboards.designer', params: { uuid: dashboardUuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PencilSquareIcon class="h-4 w-4" />
        Open designer
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="store.filters"
      :categories="store.categories"
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.loading && !store.dashboardWidgets.length" class="h-48 animate-pulse rounded-[12px] bg-zinc-100" />

    <div v-else class="relative min-h-[480px]">
      <div
        v-for="widget in positionedWidgets"
        :key="widget.uuid"
        class="absolute"
        :style="widgetStyle(widget)"
      >
        <AnalyticsWidgetCard :widget="widget" />
      </div>
      <EmptyState
        v-if="!store.dashboardWidgets.length"
        title="No widgets on this dashboard"
        description="Open the designer to add KPI or chart widgets, or add one from this view."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            :disabled="store.saving || !dashboardUuid"
            @click="showWidgetForm = true"
          >
            Add widget
          </button>
          <RouterLink
            :to="{ name: 'analytics.dashboards.designer', params: { uuid: dashboardUuid } }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Open designer
          </RouterLink>
        </template>
      </EmptyState>
    </div>

    <div
      v-if="showWidgetForm"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="showWidgetForm = false"
    >
      <div class="w-full max-w-lg overflow-hidden rounded-[12px] bg-white shadow-xl ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h3 class="text-base font-semibold text-slate-900">Add widget</h3>
          <p class="mt-0.5 text-xs text-slate-500">Add a KPI or chart tile to this dashboard.</p>
        </div>
        <form class="space-y-4 px-6 py-5" @submit.prevent="onCreateWidget">
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
            <input v-model="widgetForm.name" required class="input" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
            <SelectBox v-model="widgetForm.type" :options="widgetTypeOptions" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
            <SelectBox v-model="widgetForm.category" :options="widgetCategoryOptions" />
          </div>
          <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="showWidgetForm = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
            >
              Add widget
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ArrowLeftIcon, PencilSquareIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import AnalyticsWidgetCard from '@/modules/analytics/components/AnalyticsWidgetCard.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const store = useEnterpriseAnalyticsStore();
const route = useRoute();
const toast = useToast();
const showWidgetForm = ref(false);

const dashboardUuid = computed(() => route.params.uuid);

const positionedWidgets = computed(() =>
  (store.dashboardWidgets || []).map((widget, index) => ({
    ...widget,
    position_x: widget.position?.x ?? widget.position_x ?? (index % 3) * 4,
    position_y: widget.position?.y ?? widget.position_y ?? Math.floor(index / 3) * 3,
    width: widget.position?.width ?? widget.width ?? 4,
    height: widget.position?.height ?? widget.height ?? 3,
  }))
);

const widgetForm = reactive({
  name: '',
  type: 'kpi',
  category: '',
});

const widgetTypeOptions = [
  { value: 'kpi', label: 'KPI' },
  { value: 'line_chart', label: 'Line chart' },
  { value: 'bar_chart', label: 'Bar chart' },
  { value: 'table', label: 'Table' },
  { value: 'pie_chart', label: 'Pie chart' },
  { value: 'gauge', label: 'Gauge' },
];

const widgetCategoryOptions = computed(() => [
  { value: '', label: 'Inherit dashboard' },
  ...(store.categories || []),
]);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

function widgetStyle(widget) {
  return {
    left: `calc(${(widget.position_x / 12) * 100}% + ${widget.position_x * 4}px)`,
    top: `${widget.position_y * 88}px`,
    width: `calc(${(widget.width / 12) * 100}% - 12px)`,
    height: `${widget.height * 80}px`,
    minWidth: '180px',
  };
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.loadDashboardData(dashboardUuid.value);
}

async function onCreateWidget() {
  await store.createWidget(dashboardUuid.value, {
    name: widgetForm.name,
    type: widgetForm.type,
    category: widgetForm.category || undefined,
    query_config: {
      metric: 'event_count',
      category: widgetForm.category || undefined,
    },
  });
  showWidgetForm.value = false;
  widgetForm.name = '';
  await store.loadDashboardData(dashboardUuid.value);
}

async function load() {
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  await store.loadDashboardData(dashboardUuid.value);
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  load();
});
watch(dashboardUuid, load);
</script>
