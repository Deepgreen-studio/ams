<template>
  <div>
    <!-- <PageHeader
      :title="store.currentDashboard?.name || 'Dashboard'"
      :description="store.currentDashboard?.description || 'Widget charts and KPI tiles for this analytics view.'"
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'analytics.dashboards' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <RouterLink
          :to="{ name: 'analytics.dashboards.designer', params: { uuid: dashboardUuid } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Open designer
        </RouterLink>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="store.saving || !dashboardUuid"
          @click="showWidgetForm = true"
        >
          Add widget
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'analytics.dashboards' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <RouterLink
          :to="{ name: 'analytics.dashboards.designer', params: { uuid: dashboardUuid } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Open designer
        </RouterLink>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="store.saving || !dashboardUuid"
          @click="showWidgetForm = true"
        >
          Add widget
        </button>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      v-model="store.filters"
      :categories="store.categories"
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !store.dashboardWidgets.length" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="relative min-h-[480px]">
      <div
        v-for="widget in positionedWidgets"
        :key="widget.uuid"
        class="absolute"
        :style="widgetStyle(widget)"
      >
        <AnalyticsWidgetCard :widget="widget" />
      </div>
      <div
        v-if="!store.dashboardWidgets.length"
        class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500"
      >
        No widgets on this dashboard yet. Open the designer to add KPI or chart widgets.
      </div>
    </div>

    <div
      v-if="showWidgetForm"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="showWidgetForm = false"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Add widget</h3>
        <form class="mt-4 space-y-3" @submit.prevent="onCreateWidget">
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
            <input v-model="widgetForm.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
            <select v-model="widgetForm.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="kpi">KPI</option>
              <option value="line_chart">Line chart</option>
              <option value="bar_chart">Bar chart</option>
              <option value="table">Table</option>
              <option value="pie_chart">Pie chart</option>
              <option value="gauge">Gauge</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
            <select v-model="widgetForm.category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="">Inherit dashboard</option>
              <option v-for="category in store.categories" :key="category.value" :value="category.value">
                {{ category.label }}
              </option>
            </select>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="showWidgetForm = false">
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
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
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import AnalyticsWidgetCard from '@/modules/analytics/components/AnalyticsWidgetCard.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const route = useRoute();
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

onMounted(load);
watch(dashboardUuid, load);
</script>
