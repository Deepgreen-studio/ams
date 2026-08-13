<template>
  <div :class="framed ? 'overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100' : ''">
    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <EmptyState
      v-else-if="!consents.length"
      title="No consents found"
      description="Try adjusting your filters or record a new consent preference."
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subject</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Type
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Version
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Source
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
            v-for="item in consents"
            :key="item.uuid"
            class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
          >
            <td class="px-5 py-4">
              <RouterLink
                v-if="can('compliance.view')"
                :to="{ name: 'compliance.consents.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.subject_name || '—' }}
              </RouterLink>
              <p v-else class="font-medium text-slate-900">{{ item.subject_name || '—' }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ item.subject_email || '—' }}</p>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              {{ item.consent_type?.name || '—' }}
            </td>
            <td class="px-5 py-4">
              <ConsentStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.consent_version || '—' }}
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ item.source_label || item.source || '—' }}
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
      v-if="openMenuId && activeConsent"
      class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
      role="menu"
      :style="menuStyle"
      @click.stop
    >
      <RouterLink
        v-if="can('compliance.view')"
        :to="{ name: 'compliance.consents.show', params: { id: activeConsent.uuid } }"
        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
        role="menuitem"
        @click="closeMenu"
      >
        <EyeIcon class="h-4 w-4 text-slate-400" />
        View
      </RouterLink>
      <button
        v-if="canWithdraw(activeConsent)"
        type="button"
        class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
        role="menuitem"
        @click="onWithdraw(activeConsent)"
      >
        <NoSymbolIcon class="h-4 w-4 text-red-500" />
        Withdraw
      </button>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { EllipsisVerticalIcon, EyeIcon, NoSymbolIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import ConsentStatusBadge from '@/modules/compliance/components/ConsentStatusBadge.vue';

const props = defineProps({
  consents: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  framed: { type: Boolean, default: true },
});

const emit = defineEmits(['withdraw']);

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() => canAny('compliance.view', 'compliance.update'));
const openMenuId = ref(null);
const menuStyle = ref({});

const activeConsent = computed(
  () => props.consents.find((item) => item.uuid === openMenuId.value) || null,
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

function canWithdraw(item) {
  return Boolean(item?.granted) && can('compliance.update');
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 8 + (canWithdraw(props.consents.find((item) => item.uuid === id)) ? 2 : 1) * 36;
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

function onWithdraw(item) {
  closeMenu();
  emit('withdraw', item);
}
</script>
