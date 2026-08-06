<template>
  <div>
    <PageHeader
      title="Release calendar"
      description="Scheduled and planned deployments for the selected month."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.releases', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4"
    >
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >From</label
        >
        <input
          v-model="from"
          type="date"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >To</label
        >
        <input
          v-model="to"
          type="date"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <button
        type="button"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Apply
      </button>
    </div>

    <div
      v-if="releasesStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ releasesStore.error }}
    </div>

    <div v-if="releasesStore.loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!releasesStore.calendarReleases.length"
      title="No releases in range"
      description="Adjust the date range or schedule a release."
    />

    <div v-else class="space-y-3">
      <article
        v-for="item in releasesStore.calendarReleases"
        :key="item.uuid"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="font-semibold text-slate-900">{{ item.name }}</h3>
            <p class="mt-1 text-sm text-slate-500">
              {{ item.version_label }} · {{ item.release_type_label || item.release_type }} ·
              {{ item.status_label || item.status }}
            </p>
          </div>
          <div class="text-right text-sm text-slate-600">
            <p>Scheduled: {{ formatDate(item.scheduled_at) }}</p>
            <p>Deploy: {{ formatDate(item.deployment_date) }}</p>
          </div>
        </div>
        <RouterLink
          :to="{
            name: 'applications.releases.show',
            params: { id: route.params.id, releaseId: item.uuid },
          }"
          class="mt-3 inline-block text-sm font-medium text-brand-700 hover:underline"
        >
          Open details
        </RouterLink>
      </article>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';

const route = useRoute();
const releasesStore = useReleasesStore();

const now = new Date();
const from = ref(new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10));
const to = ref(new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10));

onMounted(reload);

async function reload() {
  await releasesStore.fetchCalendar(route.params.id, { from: from.value, to: to.value });
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
