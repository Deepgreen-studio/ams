<template>
  <div>
    <ContentSubnav />

    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-8 py-7 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div v-if="contentStore.loading" class="space-y-3 px-8 py-6">
        <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!contentStore.queue.length"
        title="Queue is clear"
        description="No content is waiting for your workflow level right now."
        class="px-8 py-6"
      />

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Title</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Type
              </th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
                Updated
              </th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in contentStore.queue"
              :key="item.uuid"
              class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            >
              <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">{{ item.title }}</p>
                <p v-if="item.slug" class="mt-0.5 font-mono text-xs text-slate-400">
                  {{ item.slug }}
                </p>
              </td>
              <td class="px-5 py-4">
                <StatusBadge :status="item.status?.slug" :label="item.status?.name" />
              </td>
              <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
                {{ item.type?.name || '—' }}
              </td>
              <td class="hidden px-5 py-4 text-slate-500 lg:table-cell">
                {{ formatDate(item.updated_at) }}
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end">
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
          @click="goTo('content.review', activeItem)"
        >
          <ClipboardDocumentCheckIcon class="h-4 w-4 text-slate-400" />
          Open review
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="goTo('content.show', activeItem)"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View details
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="goTo('content.edit', activeItem)"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  HandThumbDownIcon,
  PencilSquareIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import { useContentStore } from '@/modules/content/stores/content';

const router = useRouter();
const contentStore = useContentStore();
const openMenuId = ref(null);
const menuStyle = ref({});

const activeItem = computed(
  () => contentStore.queue.find((item) => item.uuid === openMenuId.value) || null,
);

const statCards = computed(() => [
  {
    label: 'Pending review',
    value: contentStore.statistics?.pending_review || 0,
    icon: ClockIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    label: 'Reviewed',
    value: contentStore.statistics?.reviewed || 0,
    icon: ClipboardDocumentCheckIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-600',
  },
  {
    label: 'Approved',
    value: contentStore.statistics?.approved || 0,
    icon: CheckBadgeIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Rejected',
    value: contentStore.statistics?.rejected || 0,
    icon: HandThumbDownIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
  },
]);

onMounted(() => {
  contentStore.fetchWorkflowQueue({ per_page: 20 });
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
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 132;
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

function goTo(name, item) {
  closeMenu();
  router.push({ name, params: { id: item.uuid } });
}
</script>
