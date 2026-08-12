<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!integrations.length"
      title="No integrations found"
      description="Try adjusting your search or create a new integration."
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'name')"
              >
                Integration
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'name' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'type')"
              >
                Type
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'type' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'authentication_type')"
              >
                Auth
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'authentication_type' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'status')"
              >
                Status
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'status' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'health_status')"
              >
                Health
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'health_status' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th
              v-if="hasAnyAction"
              class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in integrations"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  {{ initials(item.name) }}
                </div>
                <div class="min-w-0">
                  <p class="truncate font-semibold text-slate-900">{{ item.name }}</p>
                  <p class="truncate text-xs text-slate-500">
                    {{ item.slug }} · {{ item.company?.company_name || '—' }}
                  </p>
                </div>
              </div>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              {{ item.type_label || item.type }}
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.authentication_type_label || item.authentication_type }}
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="item.status" />
            </td>
            <td class="hidden px-5 py-4 lg:table-cell">
              <StatusBadge :status="item.health_status" kind="health" />
            </td>
            <td v-if="hasAnyAction" class="px-5 py-4">
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
        v-if="openMenuId && activeIntegration"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          v-if="can('integrations.view')"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="goTo('integrations.show', activeIntegration)"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </button>
        <button
          v-if="can('integrations.update')"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="goTo('integrations.edit', activeIntegration)"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </button>
        <button
          v-if="can('integrations.delete')"
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onDelete(activeIntegration)"
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
import { useRouter } from 'vue-router';
import {
  EllipsisVerticalIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import StatusBadge from '@/modules/integrations/components/StatusBadge.vue';

const props = defineProps({
  integrations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  sortBy: { type: String, default: 'created_at' },
  sortDir: { type: String, default: 'desc' },
});

const emit = defineEmits(['sort', 'delete']);
const router = useRouter();
const { can, canAny } = usePermissions();
const hasAnyAction = computed(() =>
  canAny('integrations.view', 'integrations.update', 'integrations.delete'),
);

const openMenuId = ref(null);
const menuStyle = ref({});

const activeIntegration = computed(
  () => props.integrations.find((item) => item.uuid === openMenuId.value) || null,
);

function initials(name) {
  return String(name || 'I')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const itemCount = [
    can('integrations.view'),
    can('integrations.update'),
    can('integrations.delete'),
  ].filter(Boolean).length;
  const menuHeight = 8 + Math.max(itemCount, 1) * 36;
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

function goTo(name, integration) {
  closeMenu();
  router.push({ name, params: { id: integration.uuid } });
}

function onDelete(integration) {
  closeMenu();
  emit('delete', integration);
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
