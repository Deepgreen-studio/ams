<template>
  <div>
    <!-- <PageHeader
      :title="store.currentDashboard?.name || 'Dashboard Designer'"
      description="Drag widgets from the library, resize on the grid, then save layout, settings, and sharing."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'analytics.dashboards' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="activePanel = activePanel === 'settings' ? 'canvas' : 'settings'"
        >
          Settings
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="activePanel = activePanel === 'sharing' ? 'canvas' : 'sharing'"
        >
          Sharing
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving || !layoutDirty"
          @click="persistLayout"
        >
          Save layout
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
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="activePanel = activePanel === 'settings' ? 'canvas' : 'settings'"
        >
          Settings
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="activePanel = activePanel === 'sharing' ? 'canvas' : 'sharing'"
        >
          Sharing
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving || !layoutDirty"
          @click="persistLayout"
        >
          Save layout
        </button>
    </Teleport>

    <AnalyticsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div v-if="activePanel === 'settings'" class="mb-4 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-4 text-sm font-semibold text-slate-900">Dashboard settings</h2>
      <DashboardSettingsPanel
        v-if="store.currentDashboard"
        :dashboard="store.currentDashboard"
        :categories="store.categories"
        :saving="store.saving"
        @cancel="activePanel = 'canvas'"
        @save="onSaveSettings"
      />
    </div>

    <div v-else-if="activePanel === 'sharing'" class="mb-4 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-4 text-sm font-semibold text-slate-900">Dashboard sharing</h2>
      <DashboardSharingPanel
        :shares="store.shares"
        :saving="store.saving"
        @share="onShare"
        @revoke="onRevoke"
      />
    </div>

    <div class="grid gap-4 xl:grid-cols-[280px_minmax(0,1fr)]">
      <WidgetLibraryPanel :library="store.widgetLibrary" @add="addWidgetFromLibrary" />

      <div class="min-w-0">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <p class="text-sm text-slate-600">
            {{ layoutItems.length }} widgets · 12-column grid · drag to move · corner to resize
          </p>
          <span v-if="layoutDirty" class="text-xs font-medium text-amber-700">Unsaved layout changes</span>
        </div>

        <div
          ref="canvasRef"
          class="relative min-h-[720px] rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3"
          :style="canvasStyle"
          @dragover.prevent
          @drop.prevent="onCanvasDrop"
        >
          <div
            v-for="item in layoutItems"
            :key="item.uuid"
            class="absolute select-none"
            :class="selectedUuid === item.uuid ? 'z-20' : 'z-10'"
            :style="itemStyle(item)"
            @mousedown="selectedUuid = item.uuid"
          >
            <div
              class="relative h-full cursor-grab active:cursor-grabbing"
              @pointerdown="onDragPointerDown($event, item)"
            >
              <AnalyticsWidgetCard :widget="item">
                <template #actions>
                  <button
                    type="button"
                    class="rounded px-1.5 py-0.5 text-xs text-rose-600 hover:bg-rose-50"
                    @click.stop="removeWidget(item)"
                  >
                    Remove
                  </button>
                </template>
              </AnalyticsWidgetCard>
              <div
                class="absolute bottom-1 right-1 h-4 w-4 cursor-se-resize rounded-sm border border-slate-300 bg-white"
                title="Resize"
                @pointerdown.stop="onResizePointerDown($event, item)"
              />
            </div>
          </div>

          <div
            v-if="!layoutItems.length && !store.loading"
            class="flex h-64 items-center justify-center text-sm text-slate-500"
          >
            Drop widgets from the library to start designing.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import AnalyticsWidgetCard from '@/modules/analytics/components/AnalyticsWidgetCard.vue';
import WidgetLibraryPanel from '@/modules/analytics/components/WidgetLibraryPanel.vue';
import DashboardSettingsPanel from '@/modules/analytics/components/DashboardSettingsPanel.vue';
import DashboardSharingPanel from '@/modules/analytics/components/DashboardSharingPanel.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const route = useRoute();
const canvasRef = ref(null);
const activePanel = ref('canvas');
const selectedUuid = ref(null);
const layoutItems = ref([]);
const layoutDirty = ref(false);
const savedSignature = ref('');

const COLUMNS = 12;
const ROW_HEIGHT = 80;
const GAP = 16;

const dashboardUuid = computed(() => route.params.uuid);

const canvasStyle = computed(() => {
  const maxY = layoutItems.value.reduce((max, item) => Math.max(max, item.position_y + item.height), 8);
  return {
    height: `${Math.max(720, maxY * (ROW_HEIGHT + GAP) + 48)}px`,
  };
});

function itemStyle(item) {
  const colWidth = canvasColumnWidth();
  return {
    left: `${item.position_x * (colWidth + GAP)}px`,
    top: `${item.position_y * (ROW_HEIGHT + GAP)}px`,
    width: `${item.width * colWidth + Math.max(0, item.width - 1) * GAP}px`,
    height: `${item.height * ROW_HEIGHT + Math.max(0, item.height - 1) * GAP}px`,
  };
}

function canvasColumnWidth() {
  const width = canvasRef.value?.clientWidth || 960;
  return (width - GAP * (COLUMNS - 1) - 24) / COLUMNS;
}

function signature(items) {
  return JSON.stringify(
    items.map((item) => ({
      uuid: item.uuid,
      x: item.position_x,
      y: item.position_y,
      w: item.width,
      h: item.height,
    }))
  );
}

function syncFromStore() {
  layoutItems.value = (store.dashboardWidgets || []).map((widget, index) => ({
    ...widget,
    position_x: widget.position?.x ?? widget.position_x ?? 0,
    position_y: widget.position?.y ?? widget.position_y ?? index * 2,
    width: widget.position?.width ?? widget.width ?? 4,
    height: widget.position?.height ?? widget.height ?? 2,
    data: widget.data || {},
  }));
  savedSignature.value = signature(layoutItems.value);
  layoutDirty.value = false;
}

async function load() {
  if (!store.categories.length) {
    await store.fetchOverview();
  }
  if (!store.widgetLibrary) {
    await store.fetchWidgetLibrary();
  }
  await store.loadDashboardData(dashboardUuid.value);
  await store.fetchShares(dashboardUuid.value);
  syncFromStore();
}

async function addWidgetFromLibrary(libraryWidget, position = null) {
  const created = await store.createWidget(dashboardUuid.value, {
    name: libraryWidget.label,
    type: libraryWidget.type,
    position_x: position?.x ?? 0,
    position_y: position?.y ?? nextY(),
    width: libraryWidget.default_width,
    height: libraryWidget.default_height,
    query_config: libraryWidget.default_query_config,
    visualization_config: libraryWidget.default_visualization_config,
  });

  await store.loadDashboardData(dashboardUuid.value);
  syncFromStore();
  if (created?.uuid) selectedUuid.value = created.uuid;
}

function nextY() {
  return layoutItems.value.reduce((max, item) => Math.max(max, item.position_y + item.height), 0);
}

function onCanvasDrop(event) {
  const raw = event.dataTransfer.getData('application/x-ams-widget');
  if (!raw || !canvasRef.value) return;

  const libraryWidget = JSON.parse(raw);
  const rect = canvasRef.value.getBoundingClientRect();
  const colWidth = canvasColumnWidth();
  const x = Math.max(0, Math.min(COLUMNS - (libraryWidget.default_width || 4), Math.floor((event.clientX - rect.left - 12) / (colWidth + GAP))));
  const y = Math.max(0, Math.floor((event.clientY - rect.top - 12) / (ROW_HEIGHT + GAP)));
  addWidgetFromLibrary(libraryWidget, { x, y });
}

let dragState = null;
let resizeState = null;

function onDragPointerDown(event, item) {
  if (event.button !== 0) return;
  selectedUuid.value = item.uuid;
  const colWidth = canvasColumnWidth();
  dragState = {
    uuid: item.uuid,
    startX: event.clientX,
    startY: event.clientY,
    originX: item.position_x,
    originY: item.position_y,
    colWidth,
  };
  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
}

function onResizePointerDown(event, item) {
  if (event.button !== 0) return;
  selectedUuid.value = item.uuid;
  const colWidth = canvasColumnWidth();
  resizeState = {
    uuid: item.uuid,
    startX: event.clientX,
    startY: event.clientY,
    originW: item.width,
    originH: item.height,
    colWidth,
  };
  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
}

function onPointerMove(event) {
  if (dragState) {
    const dx = event.clientX - dragState.startX;
    const dy = event.clientY - dragState.startY;
    const nextX = Math.max(0, Math.min(COLUMNS - 1, dragState.originX + Math.round(dx / (dragState.colWidth + GAP))));
    const nextY = Math.max(0, dragState.originY + Math.round(dy / (ROW_HEIGHT + GAP)));
    const item = layoutItems.value.find((entry) => entry.uuid === dragState.uuid);
    if (!item) return;
    item.position_x = Math.min(nextX, COLUMNS - item.width);
    item.position_y = nextY;
    layoutDirty.value = signature(layoutItems.value) !== savedSignature.value;
    return;
  }

  if (resizeState) {
    const dx = event.clientX - resizeState.startX;
    const dy = event.clientY - resizeState.startY;
    const item = layoutItems.value.find((entry) => entry.uuid === resizeState.uuid);
    if (!item) return;
    item.width = Math.max(2, Math.min(COLUMNS - item.position_x, resizeState.originW + Math.round(dx / (resizeState.colWidth + GAP))));
    item.height = Math.max(2, Math.min(12, resizeState.originH + Math.round(dy / (ROW_HEIGHT + GAP))));
    layoutDirty.value = signature(layoutItems.value) !== savedSignature.value;
  }
}

function onPointerUp() {
  dragState = null;
  resizeState = null;
  window.removeEventListener('pointermove', onPointerMove);
  window.removeEventListener('pointerup', onPointerUp);
}

async function persistLayout() {
  await store.saveLayout(dashboardUuid.value, {
    layout: {
      columns: COLUMNS,
      row_height: ROW_HEIGHT,
      gap: GAP,
    },
    widgets: layoutItems.value.map((item, index) => ({
      uuid: item.uuid,
      position_x: item.position_x,
      position_y: item.position_y,
      width: item.width,
      height: item.height,
      sort_order: index,
      is_visible: true,
    })),
  });
  await store.loadDashboardData(dashboardUuid.value);
  syncFromStore();
}

async function removeWidget(item) {
  if (!window.confirm(`Remove widget "${item.name}"?`)) return;
  await store.deleteWidget(item.uuid);
  await store.loadDashboardData(dashboardUuid.value);
  syncFromStore();
}

async function onSaveSettings(payload) {
  await store.updateDashboard(dashboardUuid.value, payload);
  activePanel.value = 'canvas';
}

async function onShare(payload) {
  await store.shareDashboard(dashboardUuid.value, payload);
}

async function onRevoke(share) {
  await store.revokeShare(dashboardUuid.value, share.uuid);
}

onMounted(load);
watch(dashboardUuid, load);
onUnmounted(() => {
  window.removeEventListener('pointermove', onPointerMove);
  window.removeEventListener('pointerup', onPointerUp);
});
</script>
