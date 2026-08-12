<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!runs.length"
      :title="emptyTitle"
      :description="emptyDescription"
      class="px-6 py-10 sm:px-8"
    >
      <template v-if="$slots.emptyAction" #action>
        <slot name="emptyAction" />
      </template>
    </EmptyState>

    <div v-else class="overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Job</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Trigger</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Duration</th>
            <th
              v-if="showRetry"
              class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="run in runs"
            :key="run.uuid"
            class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
          >
            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
              {{ formatDate(run.created_at) }}
            </td>
            <td class="px-5 py-4">
              <p class="font-medium text-slate-900">{{ run.job?.name || '—' }}</p>
              <p class="mt-0.5 font-mono text-xs text-slate-500">{{ run.job?.handler_key || '' }}</p>
              <p v-if="run.error_message" class="mt-1 text-xs text-rose-600">{{ run.error_message }}</p>
            </td>
            <td class="px-5 py-4 capitalize text-slate-600">{{ run.trigger || '—' }}</td>
            <td class="px-5 py-4">
              <span
                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                :class="statusClass(run.status)"
              >
                {{ run.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
              {{ run.duration_ms != null ? `${run.duration_ms} ms` : '—' }}
            </td>
            <td v-if="showRetry" class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === run.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(run.uuid, $event)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="$slots.footer && !loading && runs.length"
      class="border-t border-zinc-100 px-6 py-4 sm:px-8"
    >
      <slot name="footer" />
    </div>

    <Teleport to="body">
      <div
        v-if="showRetry && openMenuId && activeRun"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onRetry(activeRun)"
        >
          <ArrowPathIcon class="h-4 w-4 text-slate-400" />
          Retry
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { ArrowPathIcon, EllipsisVerticalIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';

const props = defineProps({
  runs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  showRetry: { type: Boolean, default: false },
  meta: { type: Object, default: null },
  emptyTitle: { type: String, default: 'No runs found' },
  emptyDescription: {
    type: String,
    default: 'Scheduled job executions will appear here.',
  },
});

const emit = defineEmits(['retry']);

const openMenuId = ref(null);
const menuStyle = ref({});

const activeRun = computed(
  () => props.runs.find((run) => run.uuid === openMenuId.value) || null,
);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function statusClass(status) {
  if (status === 'success') return 'bg-emerald-50 text-emerald-700';
  if (status === 'failed') return 'bg-rose-50 text-rose-700';
  if (status === 'running' || status === 'queued') return 'bg-amber-50 text-amber-700';
  if (status === 'cancelled') return 'bg-zinc-100 text-slate-500';
  return 'bg-zinc-100 text-slate-600';
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
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

function onRetry(run) {
  closeMenu();
  emit('retry', run);
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
