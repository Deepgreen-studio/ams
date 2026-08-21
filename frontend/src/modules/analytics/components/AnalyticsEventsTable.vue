<template>
  <div :class="framed ? 'overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100' : ''">
    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!events.length"
      title="No analytics events"
      description="No events were recorded in this period. Try a different date range or category."
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
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Event</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Category
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Source
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 xl:table-cell">
              Occurred
            </th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="event in events"
            :key="event.uuid"
            class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
          >
            <td class="px-5 py-4">
              <button
                type="button"
                class="text-left font-medium text-slate-900 hover:text-brand-700"
                @click="$emit('select', event)"
              >
                {{ event.event_name }}
              </button>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ subjectLabel(event) }}
              </p>
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <span
                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                :class="categoryClasses(event.category)"
              >
                {{ formatLabel(event.category) }}
              </span>
            </td>
            <td class="hidden px-5 py-4 lg:table-cell">
              <p class="text-slate-900">{{ formatLabel(event.event_source) || '—' }}</p>
              <p v-if="event.user?.full_name || event.user?.email" class="mt-0.5 text-xs text-slate-500">
                {{ event.user?.full_name || event.user?.email }}
              </p>
            </td>
            <td class="hidden whitespace-nowrap px-5 py-4 text-slate-600 xl:table-cell">
              {{ formatDate(event.occurred_at) }}
            </td>
            <td class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === event.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(event.uuid, $event)"
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
        @click="onView(activeEvent)"
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

const props = defineProps({
  events: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  framed: { type: Boolean, default: true },
});

const emit = defineEmits(['select']);

const openMenuId = ref(null);
const menuStyle = ref({});

const activeEvent = computed(
  () => props.events.find((item) => item.uuid === openMenuId.value) || null,
);

const categoryStyles = {
  business: 'bg-sky-50 text-sky-700 ring-sky-600/20',
  customer: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
  application: 'bg-amber-50 text-amber-800 ring-amber-600/20',
  api: 'bg-violet-50 text-violet-700 ring-violet-600/20',
  operational: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  security: 'bg-rose-50 text-rose-700 ring-rose-600/20',
  system: 'bg-zinc-100 text-zinc-700 ring-zinc-500/15',
  executive: 'bg-brand-50 text-brand-700 ring-brand-200',
};

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

function categoryClasses(category) {
  return categoryStyles[String(category || '').toLowerCase()] || 'bg-zinc-100 text-zinc-700 ring-zinc-500/15';
}

function formatLabel(value) {
  if (!value) {
    return '';
  }

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function subjectLabel(event) {
  if (event.subject_type) {
    const type = String(event.subject_type).split('\\').pop();
    return event.subject_id ? `${type} #${event.subject_id}` : type;
  }

  return event.application?.name || event.company?.company_name || 'No subject linked';
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

function onView(event) {
  closeMenu();
  emit('select', event);
}
</script>
