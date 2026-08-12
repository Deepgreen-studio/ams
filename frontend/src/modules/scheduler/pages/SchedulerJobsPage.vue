<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'scheduler.jobs.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create job
      </RouterLink>
    </Teleport>

    <SchedulerSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search jobs…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.job_type"
              wrapper-class="min-w-[9.5rem]"
              :options="typeOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.jobs.length"
        title="No scheduled jobs"
        description="Try adjusting your filters or create a new scheduled job."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset
          </button>
          <RouterLink
            :to="{ name: 'scheduler.jobs.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create job
          </RouterLink>
        </template>
      </EmptyState>

      <template v-else>
        <div class="overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Job</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Type</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Schedule</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Next run</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
                <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="job in store.jobs"
                :key="job.uuid"
                class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
              >
                <td class="px-5 py-4">
                  <p class="font-medium text-slate-900">{{ job.name }}</p>
                  <p class="mt-0.5 font-mono text-xs text-slate-500">{{ job.handler_key }}</p>
                </td>
                <td class="px-5 py-4 text-slate-700">{{ job.job_type_label || job.job_type }}</td>
                <td class="px-5 py-4 text-slate-600">
                  <span v-if="job.schedule_cron" class="font-mono text-xs">{{ job.schedule_cron }}</span>
                  <span v-else-if="job.delay_minutes">+{{ job.delay_minutes }}m</span>
                  <span v-else-if="job.run_at">{{ formatDate(job.run_at) }}</span>
                  <span v-else>—</span>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                  {{ formatDate(job.next_run_at) }}
                </td>
                <td class="px-5 py-4">
                  <Tooltip :text="job.is_enabled ? 'Disable job' : 'Enable job'">
                    <button
                      type="button"
                      role="switch"
                      :aria-checked="job.is_enabled"
                      :aria-label="job.is_enabled ? 'Disable job' : 'Enable job'"
                      class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                      :class="job.is_enabled ? 'bg-brand-600' : 'bg-zinc-300'"
                      :disabled="store.saving"
                      @click="toggle(job)"
                    >
                      <span
                        class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transition-transform"
                        :class="job.is_enabled ? 'translate-x-6' : 'translate-x-1'"
                      />
                    </button>
                  </Tooltip>
                </td>
                <td class="px-5 py-4">
                  <div class="relative flex justify-end">
                    <button
                      type="button"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                      :aria-expanded="openMenuId === job.uuid"
                      aria-haspopup="menu"
                      aria-label="Open actions"
                      @click.stop="toggleMenu(job.uuid, $event)"
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
          v-if="store.jobMeta?.total"
          class="border-t border-zinc-100 px-6 py-4 sm:px-8"
        >
          <Pagination
            :meta="store.jobMeta"
            :loading="store.loading"
            @change="onPageChange"
            @per-page="onPerPageChange"
          />
        </div>
      </template>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeJob"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50 disabled:opacity-50"
          role="menuitem"
          :disabled="store.saving"
          @click="onRun(activeJob)"
        >
          <PlayIcon class="h-4 w-4 text-slate-400" />
          Run
        </button>
        <RouterLink
          :to="{ name: 'scheduler.jobs.edit', params: { id: activeJob.uuid } }"
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
          @click="onDelete(activeJob)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  EllipsisVerticalIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlayIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import Tooltip from '@/components/ui/Tooltip.vue';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
const toast = useToast();
const openMenuId = ref(null);
const menuStyle = ref({});

const filters = reactive({
  search: '',
  job_type: '',
  page: 1,
  per_page: 20,
});

const activeJob = computed(
  () => store.jobs.find((job) => job.uuid === openMenuId.value) || null,
);

const typeOptions = computed(() => [
  { value: '', label: 'All types' },
  ...(store.catalog.job_types || []).map((item) => ({
    value: item.value,
    label: item.label,
  })),
]);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function load() {
  await store.fetchJobs({
    search: filters.search || undefined,
    job_type: filters.job_type || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
  filters.job_type = '';
  filters.page = 1;
  load();
}

function onPageChange(page) {
  filters.page = page;
  load();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

async function toggle(job) {
  await store.toggleJob(job.uuid, !job.is_enabled);
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 8 + 3 * 36;
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

async function onRun(job) {
  closeMenu();
  await store.runJob(job.uuid);
  await load();
}

async function onDelete(job) {
  closeMenu();
  if (!window.confirm(`Delete job "${job.name}"?`)) return;
  await store.deleteJob(job.uuid);
}

function onDocumentClick() {
  closeMenu();
}

function onScrollOrResize() {
  closeMenu();
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  load();
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
