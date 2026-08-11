<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div v-if="release" class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-if="release.approval_status === 'pending'"
          :to="{
            name: 'applications.releases.approval',
            params: { id: route.params.id, releaseId: release.uuid },
          }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-brand-600 px-5 py-2.5 text-sm font-medium text-brand-700 hover:bg-brand-50"
        >
          Approval screen
        </RouterLink>
        <button
          v-if="canSchedule"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="releasesStore.saving"
          @click="onSchedule"
        >
          Schedule
        </button>
        <button
          v-if="canDeploy"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="releasesStore.saving"
          @click="onDeploy"
        >
          Mark deployed
        </button>
        <button
          v-if="canRollback"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-rose-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
          :disabled="releasesStore.saving"
          @click="onRollback"
        >
          Rollback
        </button>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="releasesStore.loading && !release"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="release" class="grid gap-4 lg:grid-cols-3">
      <section class="space-y-4 lg:col-span-2">
        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
                {{ release.name }}
              </h2>
              <p v-if="release.plan_summary" class="mt-1 text-sm text-slate-500">
                {{ release.plan_summary }}
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <ReleaseStatusBadge :status="release.status" :label="release.status_label" />
              <span
                class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
                :class="approvalClasses"
              >
                <span class="h-1.5 w-1.5 rounded-full" :class="approvalDot" />
                {{ release.approval_status_label || approvalLabel }}
              </span>
            </div>
          </div>

          <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div
              v-for="field in detailFields"
              :key="field.label"
              class="rounded-[12px] bg-zinc-50 px-4 py-3.5"
            >
              <p class="text-xs font-medium text-zinc-500">{{ field.label }}</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ field.value }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
          <h3 class="text-sm font-semibold text-slate-900">Release notes</h3>
          <div v-if="!release.notes?.length" class="mt-4 text-sm text-slate-500">
            No release notes.
          </div>
          <article
            v-for="note in release.notes"
            :key="note.uuid"
            class="mt-4 border-t border-zinc-100 pt-4 first:mt-4 first:border-0 first:pt-0"
          >
            <div class="flex items-center justify-between gap-2">
              <h4 class="font-medium text-slate-900">{{ note.title }}</h4>
              <span
                class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-2.5 py-0.5 text-xs font-medium text-slate-600"
              >
                {{ note.audience }}
              </span>
            </div>
            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ note.content || '—' }}</p>
          </article>
        </div>
      </section>

      <aside class="space-y-4">
        <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 sm:p-6">
          <h3 class="text-sm font-semibold text-slate-900">Quick actions</h3>
          <div class="mt-3 space-y-2">
            <button
              v-if="
                release.approval_status === 'rejected' || release.approval_status === 'not_required'
              "
              type="button"
              class="flex h-11 w-full items-center rounded-[12px] border border-zinc-200 px-3.5 text-left text-sm font-medium text-slate-700 transition hover:bg-zinc-50 disabled:opacity-60"
              :disabled="releasesStore.saving"
              @click="onSubmitApproval"
            >
              Submit for approval
            </button>
            <RouterLink
              :to="{ name: 'applications.releases', params: { id: route.params.id } }"
              class="flex h-11 w-full items-center rounded-[12px] border border-zinc-200 px-3.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
            >
              Back to dashboard
            </RouterLink>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import ReleaseStatusBadge from '@/modules/applications/components/ReleaseStatusBadge.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const releasesStore = useReleasesStore();
const toast = useToast();
const release = computed(() => releasesStore.currentRelease);

const canSchedule = computed(
  () =>
    release.value &&
    !['deployed', 'rolled_back', 'cancelled', 'failed'].includes(release.value.status),
);
const canDeploy = computed(
  () =>
    release.value &&
    ['approved', 'scheduled', 'planned'].includes(release.value.status) &&
    ['approved', 'not_required'].includes(release.value.approval_status),
);
const canRollback = computed(() => release.value?.status === 'deployed');

const approvalLabel = computed(() =>
  String(release.value?.approval_status || 'pending')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase()),
);

const approvalClasses = computed(() => {
  switch (release.value?.approval_status) {
    case 'approved':
      return 'border-emerald-600 text-emerald-700';
    case 'rejected':
      return 'border-rose-500 text-rose-700';
    case 'not_required':
      return 'border-slate-400 text-slate-600';
    case 'pending':
    default:
      return 'border-amber-500 text-amber-700';
  }
});

const approvalDot = computed(() => {
  switch (release.value?.approval_status) {
    case 'approved':
      return 'bg-emerald-600';
    case 'rejected':
      return 'bg-rose-500';
    case 'not_required':
      return 'bg-slate-400';
    case 'pending':
    default:
      return 'bg-amber-500';
  }
});

const detailFields = computed(() => {
  const item = release.value;
  if (!item) return [];
  return [
    { label: 'Version', value: item.version_label || '—' },
    { label: 'Release type', value: item.release_type_label || item.release_type || '—' },
    { label: 'Rollback status', value: item.rollback_status_label || item.rollback_status || '—' },
    { label: 'Environment', value: item.environment?.name || '—' },
    { label: 'Scheduled', value: formatDate(item.scheduled_at) },
    {
      label: 'Deployment date',
      value: formatDate(item.deployment_date || item.deployed_at),
    },
    { label: 'Approved by', value: item.approver?.full_name || '—' },
    { label: 'Rolled back by', value: item.rolled_back_by?.full_name || '—' },
  ];
});

watch(
  () => releasesStore.error,
  (message) => {
    if (message) toast.error(message);
  },
);

watch(
  () => releasesStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

onMounted(async () => {
  try {
    await releasesStore.fetchRelease(route.params.id, route.params.releaseId);
  } catch {
    // Toast handled by watcher.
  }
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function onSchedule() {
  const value = window.prompt(
    'Schedule datetime (ISO or local recognisable date)',
    new Date().toISOString().slice(0, 16),
  );
  if (!value) return;
  try {
    await releasesStore.scheduleRelease(route.params.id, route.params.releaseId, {
      scheduled_at: new Date(value).toISOString(),
    });
  } catch {
    // Toast handled by watcher.
  }
}

async function onDeploy() {
  try {
    await releasesStore.deployRelease(route.params.id, route.params.releaseId, {});
  } catch {
    // Toast handled by watcher.
  }
}

async function onRollback() {
  const reason = window.prompt('Rollback reason');
  if (reason === null) return;
  try {
    await releasesStore.rollbackRelease(route.params.id, route.params.releaseId, {
      reason: reason || null,
      create_rollback_release: true,
    });
  } catch {
    // Toast handled by watcher.
  }
}

async function onSubmitApproval() {
  try {
    await releasesStore.submitApproval(route.params.id, route.params.releaseId);
  } catch {
    // Toast handled by watcher.
  }
}
</script>
