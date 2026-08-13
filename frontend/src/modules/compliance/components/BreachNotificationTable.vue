<template>
  <div :class="framed ? 'overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100' : ''">
    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <EmptyState
      v-else-if="!notifications.length"
      title="No notifications"
      description="Try adjusting your filters, or draft a notice from an incident record."
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Incident</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Type</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Recipient
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Sent
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
            v-for="item in notifications"
            :key="item.uuid"
            class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
          >
            <td class="px-5 py-4">
              <RouterLink
                v-if="can('compliance.view') && item.data_breach?.uuid"
                :to="{ name: 'compliance.breaches.show', params: { id: item.data_breach.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.data_breach.title || item.data_breach.breach_number }}
              </RouterLink>
              <p v-else class="font-medium text-slate-900">
                {{ item.data_breach?.title || item.data_breach?.breach_number || '—' }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{
                  [item.data_breach?.breach_number, item.channel_label || item.channel]
                    .filter(Boolean)
                    .join(' · ') || '—'
                }}
              </p>
            </td>
            <td class="px-5 py-4 text-slate-600">
              {{ item.notification_type_label || item.notification_type || '—' }}
            </td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              <p class="truncate">{{ item.recipient || '—' }}</p>
              <p v-if="item.subject" class="mt-0.5 truncate text-xs text-slate-400">{{ item.subject }}</p>
            </td>
            <td class="px-5 py-4">
              <BreachNotificationStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ formatDate(item.sent_at) }}
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
  </div>

  <Teleport to="body">
    <div
      v-if="openMenuId && activeNotification"
      class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
      role="menu"
      :style="menuStyle"
      @click.stop
    >
      <RouterLink
        v-if="can('compliance.view') && activeNotification.data_breach?.uuid"
        :to="{ name: 'compliance.breaches.show', params: { id: activeNotification.data_breach.uuid } }"
        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
        role="menuitem"
        @click="closeMenu"
      >
        <EyeIcon class="h-4 w-4 text-slate-400" />
        View incident
      </RouterLink>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { EllipsisVerticalIcon, EyeIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import BreachNotificationStatusBadge from '@/modules/compliance/components/BreachNotificationStatusBadge.vue';

const props = defineProps({
  notifications: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  framed: { type: Boolean, default: true },
});

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() => canAny('compliance.view'));
const openMenuId = ref(null);
const menuStyle = ref({});

const activeNotification = computed(
  () => props.notifications.find((item) => item.uuid === openMenuId.value) || null,
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
  const menuHeight = 8 + 36;
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
</script>
