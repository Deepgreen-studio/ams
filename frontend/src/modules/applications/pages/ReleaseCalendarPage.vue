<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'applications.releases', params: { id: route.params.id } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Dashboard
      </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div class="mb-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-wrap items-end gap-3">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500">From</label>
            <input
              v-model="from"
              type="date"
              class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none transition focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500">To</label>
            <input
              v-model="to"
              type="date"
              class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none transition focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
            @click="reload"
          >
            Apply
          </button>
          <button
            type="button"
            class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetRange"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <div v-if="releasesStore.loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div
      v-else-if="!releasesStore.calendarReleases.length"
      class="rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="No releases in range"
        description="Adjust the date range or schedule a release."
        class="px-8 py-6"
      />
    </div>

    <div v-else class="space-y-3">
      <article
        v-for="item in releasesStore.calendarReleases"
        :key="item.uuid"
        class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200 sm:p-6"
      >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h3 class="truncate text-base font-semibold text-slate-900">{{ item.name }}</h3>
              <ReleaseStatusBadge :status="item.status" :label="item.status_label" />
            </div>
            <p class="mt-1.5 text-sm text-slate-500">
              {{ item.version_label || '—' }} ·
              {{ item.release_type_label || item.release_type || '—' }}
              <span v-if="item.environment?.name"> · {{ item.environment.name }}</span>
            </p>
            <RouterLink
              :to="{
                name: 'applications.releases.show',
                params: { id: route.params.id, releaseId: item.uuid },
              }"
              class="mt-3 inline-flex text-sm font-medium text-brand-700 transition hover:text-brand-800"
            >
              Open details
            </RouterLink>
          </div>

          <div class="grid gap-2 sm:min-w-[14rem] sm:text-right">
            <div class="rounded-[12px] bg-zinc-50 px-3.5 py-2.5 sm:text-left">
              <p class="text-xs font-medium text-zinc-500">Scheduled</p>
              <p class="mt-0.5 text-sm font-semibold text-slate-900">
                {{ formatDate(item.scheduled_at) }}
              </p>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-3.5 py-2.5 sm:text-left">
              <p class="text-xs font-medium text-zinc-500">Deploy</p>
              <p class="mt-0.5 text-sm font-semibold text-slate-900">
                {{ formatDate(item.deployment_date || item.deployed_at) }}
              </p>
            </div>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import ReleaseStatusBadge from '@/modules/applications/components/ReleaseStatusBadge.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const releasesStore = useReleasesStore();
const toast = useToast();

const now = new Date();
const defaultFrom = () =>
  new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
const defaultTo = () =>
  new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);

const from = ref(defaultFrom());
const to = ref(defaultTo());

watch(
  () => releasesStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load calendar');
  },
);

onMounted(reload);

async function reload() {
  try {
    await releasesStore.fetchCalendar(route.params.id, { from: from.value, to: to.value });
  } catch {
    // Toast handled by watcher.
  }
}

function resetRange() {
  from.value = defaultFrom();
  to.value = defaultTo();
  reload();
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
