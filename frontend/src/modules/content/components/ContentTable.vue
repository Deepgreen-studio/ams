<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!contents.length"
      title="No content found"
      description="Create pages, blogs, FAQs, and other headless content entries."
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Title</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Type
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Category
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 xl:table-cell">
              Updated
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in contents"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <p class="font-semibold text-slate-900">{{ item.title }}</p>
              <p class="mt-0.5 font-mono text-xs text-slate-400">{{ item.slug }}</p>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              {{ item.type?.name || '—' }}
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="item.status?.slug" :label="item.status?.name" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.category?.name || '—' }}
            </td>
            <td class="hidden px-5 py-4 text-slate-500 xl:table-cell">
              {{ formatDate(item.updated_at) }}
            </td>
            <td class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === item.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(item.uuid)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>

                <div
                  v-if="openMenuId === item.uuid"
                  class="absolute right-0 top-10 z-20 w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
                  role="menu"
                  @click.stop
                >
                  <RouterLink
                    :to="{ name: 'content.show', params: { id: item.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <EyeIcon class="h-4 w-4 text-slate-400" />
                    View
                  </RouterLink>
                  <RouterLink
                    :to="{ name: 'content.edit', params: { id: item.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <PencilSquareIcon class="h-4 w-4 text-slate-400" />
                    Edit
                  </RouterLink>
                  <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                    role="menuitem"
                    @click="onDelete(item)"
                  >
                    <TrashIcon class="h-4 w-4 text-red-500" />
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-8 py-4">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
  EllipsisVerticalIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';

defineProps({
  contents: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['delete']);

const openMenuId = ref(null);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onDelete(content) {
  closeMenu();
  emit('delete', content);
}

function onDocumentClick() {
  closeMenu();
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});
</script>
