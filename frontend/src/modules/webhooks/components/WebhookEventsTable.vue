<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!events.length"
      title="No webhook events found"
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Event</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Module
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Description
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in events"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <p class="font-semibold text-slate-900">{{ item.label }}</p>
              <p class="font-mono text-xs text-slate-500">{{ item.name }}</p>
              <p class="mt-0.5 text-xs capitalize text-slate-500 md:hidden">
                {{ formatModule(item.source_module) }}
              </p>
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <span
                class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium capitalize text-slate-600"
              >
                {{ formatModule(item.source_module) }}
              </span>
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="item.status" />
            </td>
            <td class="hidden max-w-md px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.description || '—' }}
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
        v-if="openMenuId && activeEvent"
        class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click.stop="onView(activeEvent)"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click.stop="onCopy(activeEvent)"
        >
          <ClipboardDocumentIcon class="h-4 w-4 text-slate-400" />
          Copy name
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import {
  ClipboardDocumentIcon,
  EllipsisVerticalIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/webhooks/components/StatusBadge.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  events: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['view']);
const toast = useToast();

const openMenuId = ref(null);
const menuStyle = ref({});
const ignoreDocumentClick = ref(false);

const activeEvent = computed(
  () => props.events.find((item) => item.uuid === openMenuId.value) || null,
);

function formatModule(value) {
  return String(value || '—').replaceAll('_', ' ');
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 8 + 2 * 36;
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

function onView(event) {
  if (!event) return;
  const item = event;
  closeMenu();
  emit('view', item);
}

async function onCopy(event) {
  if (!event) return;
  closeMenu();
  try {
    await navigator.clipboard.writeText(event.name || '');
    toast.success('Event name copied.');
  } catch {
    toast.error('Unable to copy event name.');
  }
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
