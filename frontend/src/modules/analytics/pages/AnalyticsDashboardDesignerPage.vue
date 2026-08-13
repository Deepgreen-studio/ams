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
      <RouterLink
        v-if="dashboardUuid"
        :to="{ name: 'analytics.dashboards.show', params: { uuid: dashboardUuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <EyeIcon class="h-4 w-4" />
        View
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border px-5 py-2.5 text-sm font-medium transition"
        :class="
          activePanel === 'settings'
            ? 'border-brand-200 bg-brand-50 text-brand-700'
            : 'border-zinc-200 bg-white text-slate-700 hover:bg-zinc-50'
        "
        @click="togglePanel('settings')"
      >
        <Cog6ToothIcon class="h-4 w-4" />
        Settings
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border px-5 py-2.5 text-sm font-medium transition"
        :class="
          activePanel === 'sharing'
            ? 'border-brand-200 bg-brand-50 text-brand-700'
            : 'border-zinc-200 bg-white text-slate-700 hover:bg-zinc-50'
        "
        @click="togglePanel('sharing')"
      >
        <ShareIcon class="h-4 w-4" />
        Sharing
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving || !layoutDirty"
        @click="persistLayout"
      >
        <CheckIcon class="h-4 w-4" />
        Save layout
      </button>
    </Teleport>

    <AnalyticsSubnav />

    <div
      v-if="layoutDirty"
      class="mb-4 flex items-start gap-3 rounded-[12px] bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-100"
    >
      <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
      <p>Unsaved layout changes. Drag to move, use the corner to resize, then save.</p>
    </div>

    <div v-if="store.loading && !layoutItems.length" class="grid gap-4 xl:grid-cols-[280px_minmax(0,1fr)]">
      <div class="h-[32rem] animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="h-[32rem] animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !layoutItems.length"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load designer</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading this dashboard layout again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Retry
      </button>
    </div>

    <div v-else class="grid gap-4 xl:grid-cols-[280px_minmax(0,1fr)]">
      <WidgetLibraryPanel :library="store.widgetLibrary" @add="addWidgetFromLibrary" />

      <div class="min-w-0">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <p class="text-sm text-slate-600">
            {{ layoutItems.length }} widgets · 12-column grid · drag to move · corner to resize
          </p>
        </div>

        <div
          ref="canvasRef"
          class="relative min-h-[720px] rounded-[12px] bg-zinc-50 p-3 ring-1 ring-dashed ring-zinc-200"
          :style="canvasStyle"
          @dragover.prevent
          @drop.prevent="onCanvasDrop"
        >
          <div
            v-for="item in layoutItems"
            :key="item.uuid"
            class="absolute select-none rounded-[12px] transition"
            :class="selectedUuid === item.uuid ? 'z-20 ring-2 ring-brand-500' : 'z-10 ring-0'"
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
                    class="inline-flex h-8 w-8 items-center justify-center rounded-[10px] text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                    aria-label="Remove widget"
                    @pointerdown.stop
                    @click.stop="pendingRemove = item"
                  >
                    <XMarkIcon class="h-4 w-4" />
                  </button>
                </template>
              </AnalyticsWidgetCard>
              <button
                type="button"
                class="absolute bottom-1.5 right-1.5 inline-flex h-5 w-5 cursor-se-resize items-end justify-end rounded-[6px] text-slate-300 hover:text-brand-600"
                title="Resize"
                aria-label="Resize widget"
                @pointerdown.stop="onResizePointerDown($event, item)"
              >
                <span class="mb-0.5 mr-0.5 h-2.5 w-2.5 border-b-2 border-r-2 border-current" />
              </button>
            </div>
          </div>

          <EmptyState
            v-if="!layoutItems.length && !store.loading"
            title="Canvas is empty"
            description="Drop widgets from the library to start designing this dashboard."
            class="h-64 bg-transparent"
          />
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="activePanel !== 'canvas'" class="fixed inset-0 z-40">
        <div class="absolute inset-0 bg-slate-900/40" @click="activePanel = 'canvas'" />
        <aside class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5">
            <div>
              <h2 class="text-base font-semibold text-slate-900">
                {{ activePanel === 'settings' ? 'Dashboard settings' : 'Dashboard sharing' }}
              </h2>
              <p class="mt-0.5 text-xs text-slate-500">
                {{
                  activePanel === 'settings'
                    ? 'Name, visibility, and refresh for this board.'
                    : 'Grant view or edit access to a role, user, or company.'
                }}
              </p>
            </div>
            <button
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-[10px] text-slate-400 hover:bg-zinc-100 hover:text-slate-700"
              aria-label="Close panel"
              @click="activePanel = 'canvas'"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <div class="scrollbar-light flex-1 overflow-y-auto px-6 py-5">
            <DashboardSettingsPanel
              v-if="activePanel === 'settings' && store.currentDashboard"
              :dashboard="store.currentDashboard"
              :categories="store.categories"
              :saving="store.saving"
              @cancel="activePanel = 'canvas'"
              @save="onSaveSettings"
            />
            <DashboardSharingPanel
              v-else-if="activePanel === 'sharing'"
              :shares="store.shares"
              :saving="store.saving"
              @share="onShare"
              @revoke="onRevoke"
            />
          </div>
        </aside>
      </div>
    </Teleport>

    <DeleteConfirmation
      :open="Boolean(pendingRemove)"
      title="Remove widget"
      :message="`Remove widget “${pendingRemove?.name}”? This cannot be undone.`"
      confirm-label="Remove"
      :loading="store.saving"
      @cancel="pendingRemove = null"
      @confirm="confirmRemoveWidget"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ArrowLeftIcon,
  CheckIcon,
  Cog6ToothIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  ShareIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import AnalyticsWidgetCard from '@/modules/analytics/components/AnalyticsWidgetCard.vue';
import WidgetLibraryPanel from '@/modules/analytics/components/WidgetLibraryPanel.vue';
import DashboardSettingsPanel from '@/modules/analytics/components/DashboardSettingsPanel.vue';
import DashboardSharingPanel from '@/modules/analytics/components/DashboardSharingPanel.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';

const store = useEnterpriseAnalyticsStore();
const route = useRoute();
const toast = useToast();
const canvasRef = ref(null);
const activePanel = ref('canvas');
const selectedUuid = ref(null);
const layoutItems = ref([]);
const layoutDirty = ref(false);
const savedSignature = ref('');
const pendingRemove = ref(null);

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

function togglePanel(panel) {
  activePanel.value = activePanel.value === panel ? 'canvas' : panel;
}

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

async function confirmRemoveWidget() {
  if (!pendingRemove.value) return;
  try {
    await store.deleteWidget(pendingRemove.value.uuid);
    pendingRemove.value = null;
    await store.loadDashboardData(dashboardUuid.value);
    syncFromStore();
  } catch {
    pendingRemove.value = null;
  }
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

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  load();
});
watch(dashboardUuid, load);
onUnmounted(() => {
  window.removeEventListener('pointermove', onPointerMove);
  window.removeEventListener('pointerup', onPointerUp);
});
</script>
