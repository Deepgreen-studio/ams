<template>
  <div>
    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-52 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!items.length"
      title="No media found"
      description="Upload files or adjust filters to see assets."
      class="rounded-[12px] bg-white px-8 py-10 ring-1 ring-zinc-100"
    />

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <article
        v-for="item in items"
        :key="item.uuid"
        class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <button type="button" class="block w-full bg-zinc-50" @click="$emit('preview', item)">
          <img
            v-if="item.is_image"
            :src="item.url"
            :alt="item.alt_text || item.name"
            class="h-40 w-full object-cover"
          />
          <div
            v-else
            class="flex h-40 items-center justify-center text-sm font-semibold uppercase tracking-wide text-slate-400"
          >
            {{ item.extension }}
          </div>
        </button>

        <div class="p-4">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-900" :title="item.original_name">
                {{ item.original_name }}
              </p>
              <p class="mt-1 text-xs text-slate-500">
                {{ item.type }} · {{ item.human_size }}
                <span
                  class="ml-1 inline-flex items-center rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600"
                >
                  v{{ item.version }}
                </span>
              </p>
            </div>

            <button
              type="button"
              class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
              :aria-expanded="openMenuId === item.uuid"
              aria-haspopup="menu"
              aria-label="Open actions"
              @click.stop="toggleMenu(item.uuid, $event)"
            >
              <EllipsisVerticalIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </article>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeItem"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="emitAction('preview')"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          Preview
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="emitAction('download')"
        >
          <ArrowDownTrayIcon class="h-4 w-4 text-slate-400" />
          Download
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="emitAction('replace')"
        >
          <ArrowPathIcon class="h-4 w-4 text-slate-400" />
          Replace
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="emitAction('versions')"
        >
          <ClockIcon class="h-4 w-4 text-slate-400" />
          History
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="emitAction('delete')"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  ClockIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['preview', 'download', 'replace', 'versions', 'delete']);

const openMenuId = ref(null);
const menuStyle = ref({});

const activeItem = computed(
  () => props.items.find((item) => item.uuid === openMenuId.value) || null,
);

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 220;
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
}

function closeMenu() {
  openMenuId.value = null;
}

function emitAction(name) {
  if (!activeItem.value) return;
  emit(name, activeItem.value);
  closeMenu();
}

onMounted(() => {
  document.addEventListener('click', closeMenu);
  window.addEventListener('scroll', closeMenu, true);
  window.addEventListener('resize', closeMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu);
  window.removeEventListener('scroll', closeMenu, true);
  window.removeEventListener('resize', closeMenu);
});
</script>
