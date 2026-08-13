<template>
  <div :class="framed ? 'overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100' : ''">
    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!items.length"
      title="No audit entries"
      description="Before/after change trails will appear here as records are created, updated, or deleted."
      class="px-6 py-10 sm:px-8"
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="scrollbar-light overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Change</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Actor
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Action</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              When
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 xl:table-cell">
              Company
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in items"
            :key="item.uuid"
            class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
          >
            <td class="px-5 py-4">
              <button
                type="button"
                class="text-left font-medium text-slate-900 hover:text-brand-700"
                @click="$emit('select', item)"
              >
                {{ formatModule(item.module) }}
              </button>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ changedSummary(item) }}
              </p>
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <p class="text-slate-900">{{ item.user?.full_name || item.user?.email || 'System' }}</p>
              <p v-if="item.user?.email && item.user?.full_name" class="mt-0.5 text-xs text-slate-500">
                {{ item.user.email }}
              </p>
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="item.action" />
            </td>
            <td class="hidden whitespace-nowrap px-5 py-4 text-slate-600 lg:table-cell">
              {{ formatDate(item.created_at) }}
            </td>
            <td class="hidden px-5 py-4 text-slate-600 xl:table-cell">
              {{ item.company?.company_name || '—' }}
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
        type="button"
        class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
        role="menuitem"
        @click="onView(activeItem)"
      >
        <EyeIcon class="h-4 w-4 text-slate-400" />
        View details
      </button>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { EllipsisVerticalIcon, EyeIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/audit/components/StatusBadge.vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  framed: { type: Boolean, default: true },
});

const emit = defineEmits(['select']);

const openMenuId = ref(null);
const menuStyle = ref({});

const activeItem = computed(
  () => props.items.find((item) => item.uuid === openMenuId.value) || null,
);

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

function formatModule(value) {
  if (!value) {
    return 'Unknown module';
  }

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function changedSummary(item) {
  const fields = Array.isArray(item.changed_fields) ? item.changed_fields.filter(Boolean) : [];
  if (fields.length) {
    return fields.slice(0, 4).join(', ') + (fields.length > 4 ? ` +${fields.length - 4}` : '');
  }

  return item.reason || item.ip_address || 'No field-level changes recorded';
}

function formatDate(value) {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 44;
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
  emit('select', item);
}
</script>
