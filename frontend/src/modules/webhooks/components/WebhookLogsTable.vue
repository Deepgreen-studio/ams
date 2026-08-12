<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!logs.length"
      title="No webhook logs found"
      description="Try adjusting your search or filters."
      class="px-8 py-6"
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Webhook</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Event
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in logs"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
              {{ formatDate(item.created_at) }}
            </td>
            <td class="px-5 py-4">
              <p class="font-semibold text-slate-900">{{ item.webhook?.name || '—' }}</p>
              <p class="text-xs capitalize text-slate-500">
                {{ item.direction }} · {{ item.response_status || '—' }}
              </p>
              <p class="mt-0.5 truncate text-xs text-slate-500 lg:hidden">
                {{ item.event_name || '—' }}
              </p>
            </td>
            <td class="hidden px-5 py-4 font-mono text-xs text-slate-700 lg:table-cell">
              {{ item.event_name || '—' }}
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="item.status" kind="delivery" />
            </td>
            <td class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === item.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(item.uuid, $event)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-8 py-5">
      <slot name="footer" />
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeLog"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click.stop="onView(activeLog)"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </button>
        <button
          v-if="canRetry(activeLog)"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50 disabled:opacity-60"
          role="menuitem"
          :disabled="retrying"
          @click.stop="onRetry(activeLog)"
        >
          <ArrowPathIcon class="h-4 w-4 text-slate-400" />
          Retry
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import {
  ArrowPathIcon,
  EllipsisVerticalIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/webhooks/components/StatusBadge.vue';

const props = defineProps({
  logs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  retrying: { type: Boolean, default: false },
});

const emit = defineEmits(['view', 'retry']);

const openMenuId = ref(null);
const menuStyle = ref({});
const ignoreDocumentClick = ref(false);

const activeLog = computed(
  () => props.logs.find((item) => item.uuid === openMenuId.value) || null,
);

function canRetry(item) {
  return ['failed', 'retrying'].includes(item?.status);
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const itemCount = canRetry(props.logs.find((item) => item.uuid === id)) ? 2 : 1;
  const menuHeight = 8 + itemCount * 36;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  openMenuId.value = id;
  ignoreDocumentClick.value = true;
  nextTick(() => {
    ignoreDocumentClick.value = false;
  });
}

function closeMenu() {
  openMenuId.value = null;
}

function onView(log) {
  if (!log) return;
  const item = log;
  closeMenu();
  emit('view', item);
}

function onRetry(log) {
  if (!log) return;
  const item = log;
  closeMenu();
  emit('retry', item);
}

function onDocumentClick() {
  if (ignoreDocumentClick.value) return;
  closeMenu();
}

function onScrollOrResize() {
  closeMenu();
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  window.addEventListener('scroll', onScrollOrResize, true);
  window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  window.removeEventListener('scroll', onScrollOrResize, true);
  window.removeEventListener('resize', onScrollOrResize);
});
</script>
