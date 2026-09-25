<template>
  <div :class="embedded ? '' : 'rounded-[12px] bg-white ring-1 ring-zinc-100'">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
      <slot name="toolbar" />
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
          <tr class="border-b border-zinc-100">
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 text-left text-sm font-semibold text-slate-600"
            >
              <button
                v-if="column.sortable"
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-slate-800"
                @click="$emit('sort', column.key)"
              >
                {{ column.label }}
                <span class="text-base leading-none text-slate-400">
                  {{ sortBy === column.key ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
              <span v-else>{{ column.label }}</span>
            </th>
            <th class="px-6 py-4 text-right text-sm font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody v-if="loading">
          <tr v-for="n in 4" :key="n">
            <td colspan="12" class="px-5 py-3">
              <div class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="!items.length">

          <tr>

            <td colspan="12" class="p-0">
              <EmptyState
                :title="emptyTitle"
                :description="emptyDescription"
                >
                <template v-if="$slots['empty-action']" #action>
                <slot name="empty-action" />
                </template>
              </EmptyState>
            </td>

          </tr>

        </tbody>

        <tbody v-else>
          <tr
            v-for="item in items"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            :class="showView ? 'cursor-pointer' : ''"
            @click="showView && $emit('view', item)"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 text-slate-700"
            >
              <slot :name="`cell-${column.key}`" :item="item">
                <span
                  :class="
                    column.key === 'name' || column.key === 'branch_name'
                      ? 'font-semibold text-slate-900'
                      : ''
                  "
                >
                  {{ item[column.key] || '-' }}
                </span>
              </slot>
            </td>
            <td class="px-6 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
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

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-6 py-5 sm:px-8">
      <slot name="footer" />
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeItem"
        class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          v-if="showView"
          type="button"
          class="flex w-full cursor-pointer items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onView(activeItem)"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </button>
        <button
          type="button"
          class="flex w-full cursor-pointer items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onEdit(activeItem)"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </button>
        <button
          type="button"
          class="flex w-full cursor-pointer items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onDelete(activeItem)"
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
import { EllipsisVerticalIcon, EyeIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  embedded: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'No records' },
  emptyDescription: { type: String, default: 'Nothing to display yet.' },
  showView: { type: Boolean, default: false },
  sortBy: { type: String, default: '' },
  sortDir: { type: String, default: 'asc' },
});

const emit = defineEmits(['view', 'edit', 'delete', 'sort']);

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
  const menuWidth = 176;
  const itemCount = props.showView ? 3 : 2;
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
}

function closeMenu() {
  openMenuId.value = null;
}

function onView(item) {
  closeMenu();
  emit('view', item);
}

function onEdit(item) {
  closeMenu();
  emit('edit', item);
}

function onDelete(item) {
  closeMenu();
  emit('delete', item);
}

function onDocumentClick() {
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
