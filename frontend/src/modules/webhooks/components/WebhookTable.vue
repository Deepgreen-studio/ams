<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div class="overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Webhook</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Direction
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Created At</th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody v-if="loading">
          <tr v-for="n in 5" :key="n">
            <td colspan="12" class="px-5 py-3">
              <div class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="!webhooks.length">

          <tr>

            <td colspan="12" class="p-0">
              <EmptyState
                title="No webhooks found"
                description="No results match the current search."
                >
                <template #action>
                <slot name="empty-action" />
                </template>
              </EmptyState>
            </td>

          </tr>

        </tbody>

        <tbody v-else>
          <tr
            v-for="item in webhooks"
            :key="item.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            :class="can('webhooks.view') ? 'cursor-pointer' : ''"
            @click="openDetails(item)"
          >
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  {{ initials(item.name) }}
                </div>
                <div class="min-w-0">
                  <RouterLink
                    v-if="can('webhooks.view')"
                    :to="{ name: 'webhooks.show', params: { id: item.uuid } }"
                    class="truncate font-semibold text-slate-900 hover:text-brand-700"
                  >
                    {{ item.name }}
                  </RouterLink>
                  <p v-else class="truncate font-semibold text-slate-900">{{ item.name }}</p>
                  <p class="truncate text-xs text-slate-500">
                    {{ item.slug }} · {{ item.company?.company_name || '—' }}
                  </p>
                </div>
              </div>
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <DirectionBadge :direction="item.direction" />
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="item.status" />
            </td>
            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
              {{ formatDate(item.created_at) || '—' }}
            </td>
            <td class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  v-if="hasAnyAction"
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
        v-if="openMenuId && activeWebhook"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          v-if="can('webhooks.view')"
          :to="{ name: 'webhooks.show', params: { id: activeWebhook.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </RouterLink>
        <template v-if="isTrashed(activeWebhook)">
          <button
            v-if="can('webhooks.restore')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="onRestore(activeWebhook)"
          >
            <ArrowUturnLeftIcon class="h-4 w-4 text-slate-400" />
            Restore
          </button>
        </template>
        <template v-else>
          <RouterLink
            v-if="can('webhooks.test')"
            :to="{ name: 'webhooks.tester', params: { id: activeWebhook.uuid } }"
            class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="closeMenu"
          >
            <BeakerIcon class="h-4 w-4 text-slate-400" />
            Test
          </RouterLink>
          <RouterLink
            v-if="can('webhooks.update')"
            :to="{ name: 'webhooks.edit', params: { id: activeWebhook.uuid } }"
            class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="closeMenu"
          >
            <PencilSquareIcon class="h-4 w-4 text-slate-400" />
            Edit
          </RouterLink>
          <button
            v-if="can('webhooks.delete')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
            role="menuitem"
            @click="onDelete(activeWebhook)"
          >
            <TrashIcon class="h-4 w-4 text-red-500" />
            Soft Delete
          </button>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  ArrowUturnLeftIcon,
  BeakerIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import DirectionBadge from '@/modules/webhooks/components/DirectionBadge.vue';
import StatusBadge from '@/modules/webhooks/components/StatusBadge.vue';
import { formatDate } from '@/utils/formatters';

const props = defineProps({
  webhooks: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['delete', 'restore']);
const router = useRouter();
const { can, canAny } = usePermissions();
const hasAnyAction = computed(() =>
  canAny('webhooks.view', 'webhooks.test', 'webhooks.update', 'webhooks.delete', 'webhooks.restore'),
);

function openDetails(webhook) {
  if (!webhook?.uuid || !can('webhooks.view')) {
    return;
  }

  router.push({ name: 'webhooks.show', params: { id: webhook.uuid } });
}

const openMenuId = ref(null);
const menuStyle = ref({});

const activeWebhook = computed(
  () => props.webhooks.find((item) => item.uuid === openMenuId.value) || null,
);

function isTrashed(webhook) {
  return Boolean(webhook?.deleted_at);
}

function initials(name) {
  return String(name || 'W')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const webhook = props.webhooks.find((item) => item.uuid === id);
  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const itemCount = isTrashed(webhook)
    ? [can('webhooks.view'), can('webhooks.restore')].filter(Boolean).length
    : [
        can('webhooks.view'),
        can('webhooks.test'),
        can('webhooks.update'),
        can('webhooks.delete'),
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

function onDelete(webhook) {
  closeMenu();
  emit('delete', webhook);
}

function onRestore(webhook) {
  closeMenu();
  emit('restore', webhook);
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
