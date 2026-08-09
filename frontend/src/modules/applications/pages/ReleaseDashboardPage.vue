<template>
  <div>
    <!-- <PageHeader
      :title="title"
      description="Plan, schedule, approve, deploy, and roll back application releases."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.releases.calendar', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Calendar
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.releases.timeline', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Timeline
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.releases.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Plan release
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'applications.releases.calendar', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Calendar
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.releases.timeline', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Timeline
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.releases.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Plan release
        </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="releasesStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ releasesStore.error }}
    </div>
    <div
      v-if="releasesStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ releasesStore.successMessage }}
    </div>

    <div v-if="releasesStore.summary" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ releasesStore.summary.total }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Awaiting approval</p>
        <p class="mt-1 text-2xl font-semibold text-amber-700">
          {{ releasesStore.summary.awaiting_approval }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Deployed</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-700">
          {{ releasesStore.summary.deployed }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Rolled back</p>
        <p class="mt-1 text-2xl font-semibold text-rose-700">
          {{ releasesStore.summary.rolled_back }}
        </p>
      </div>
    </div>

    <div v-if="releasesStore.loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!releasesStore.releases.length"
      title="No releases"
      description="Create a release plan linked to an application version."
    >
      <template #action>
        <RouterLink
          :to="{ name: 'applications.releases.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Plan release
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Release</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Version</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Type
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Schedule
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in releasesStore.releases" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.environment?.name || 'No environment' }}</p>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.version_label }}</td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.release_type_label || item.release_type }}
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                :class="statusClass(item.status)"
              >
                {{ item.status_label || item.status }}
              </span>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ formatDate(item.scheduled_at || item.deployment_date) }}
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{
                  name: 'applications.releases.show',
                  params: { id: route.params.id, releaseId: item.uuid },
                }"
                class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
              >
                Details
              </RouterLink>
              <RouterLink
                v-if="item.approval_status === 'pending'"
                :to="{
                  name: 'applications.releases.approval',
                  params: { id: route.params.id, releaseId: item.uuid },
                }"
                class="rounded-md px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50"
              >
                Approve
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';

const route = useRoute();
const releasesStore = useReleasesStore();

const title = computed(() => {
  const name = releasesStore.application?.name;
  return name ? `${name} releases` : 'Release Dashboard';
});

onMounted(() => {
  releasesStore.fetchDashboard(route.params.id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function statusClass(status) {
  switch (status) {
    case 'deployed':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'approved':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'pending_approval':
    case 'scheduled':
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    case 'failed':
    case 'rolled_back':
    case 'rejected':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'cancelled':
      return 'bg-slate-50 text-slate-600 ring-slate-500/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
}
</script>
