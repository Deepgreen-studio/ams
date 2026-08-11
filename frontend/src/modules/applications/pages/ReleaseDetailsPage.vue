<template>
  <div>
    <!-- <PageHeader
      :title="release?.name || 'Release details'"
      description="Release planning, deployment status, approval, and rollback."
    >
      <template #actions>
        <template v-if="release">
          <RouterLink
            v-if="release.approval_status === 'pending'"
            :to="{
              name: 'applications.releases.approval',
              params: { id: route.params.id, releaseId: release.uuid },
            }"
            class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50"
          >
            Approval screen
          </RouterLink>
          <button
            v-if="canSchedule"
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
            :disabled="releasesStore.saving"
            @click="onSchedule"
          >
            Schedule
          </button>
          <button
            v-if="canDeploy"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="releasesStore.saving"
            @click="onDeploy"
          >
            Mark deployed
          </button>
          <button
            v-if="canRollback"
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
            :disabled="releasesStore.saving"
            @click="onRollback"
          >
            Rollback
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <div v-if="release" class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-if="release.approval_status === 'pending'"
          :to="{
            name: 'applications.releases.approval',
            params: { id: route.params.id, releaseId: release.uuid },
          }"
          class="rounded-[12px] border border-amber-300 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50"
        >
          Approval screen
        </RouterLink>
        <button
          v-if="canSchedule"
          type="button"
          class="rounded-[12px] border border-zinc-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="releasesStore.saving"
          @click="onSchedule"
        >
          Schedule
        </button>
        <button
          v-if="canDeploy"
          type="button"
          class="rounded-[12px] bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="releasesStore.saving"
          @click="onDeploy"
        >
          Mark deployed
        </button>
        <button
          v-if="canRollback"
          type="button"
          class="rounded-[12px] bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
          :disabled="releasesStore.saving"
          @click="onRollback"
        >
          Rollback
        </button>
      </div>
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

    <div
      v-if="releasesStore.loading && !release"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="release" class="grid gap-4 lg:grid-cols-3">
      <section class="space-y-4 lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <dl class="grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Version</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">{{ release.version_label }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Release type</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ release.release_type_label || release.release_type }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Deployment status</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ release.status_label || release.status }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Approval</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ release.approval_status_label || release.approval_status }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Rollback status</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ release.rollback_status_label || release.rollback_status }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Environment</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ release.environment?.name || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Scheduled</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ formatDate(release.scheduled_at) }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Deployment date</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ formatDate(release.deployment_date || release.deployed_at) }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Approved by</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ release.approver?.full_name || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Rolled back by</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900">
                {{ release.rolled_back_by?.full_name || '—' }}
              </dd>
            </div>
          </dl>
          <p v-if="release.plan_summary" class="mt-4 text-sm text-slate-600">
            {{ release.plan_summary }}
          </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <h3 class="text-sm font-semibold text-slate-900">Release notes</h3>
          <div v-if="!release.notes?.length" class="mt-3 text-sm text-slate-500">
            No release notes.
          </div>
          <article
            v-for="note in release.notes"
            :key="note.uuid"
            class="mt-4 border-t border-slate-100 pt-4 first:border-0 first:pt-0"
          >
            <div class="flex items-center justify-between gap-2">
              <h4 class="font-medium text-slate-900">{{ note.title }}</h4>
              <span class="text-xs text-slate-500">{{ note.audience }}</span>
            </div>
            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ note.content || '—' }}</p>
          </article>
        </div>
      </section>

      <aside class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="text-sm font-semibold text-slate-900">Quick actions</h3>
          <div class="mt-3 space-y-2">
            <button
              v-if="
                release.approval_status === 'rejected' || release.approval_status === 'not_required'
              "
              type="button"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-left text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-60"
              :disabled="releasesStore.saving"
              @click="onSubmitApproval"
            >
              Submit for approval
            </button>
            <RouterLink
              :to="{ name: 'applications.releases', params: { id: route.params.id } }"
              class="block w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm text-slate-700 hover:bg-slate-50"
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
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';

const route = useRoute();
const releasesStore = useReleasesStore();
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

onMounted(() => {
  releasesStore.fetchRelease(route.params.id, route.params.releaseId);
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
  await releasesStore.scheduleRelease(route.params.id, route.params.releaseId, {
    scheduled_at: new Date(value).toISOString(),
  });
}

async function onDeploy() {
  await releasesStore.deployRelease(route.params.id, route.params.releaseId, {});
}

async function onRollback() {
  const reason = window.prompt('Rollback reason');
  if (reason === null) return;
  await releasesStore.rollbackRelease(route.params.id, route.params.releaseId, {
    reason: reason || null,
    create_rollback_release: true,
  });
}

async function onSubmitApproval() {
  await releasesStore.submitApproval(route.params.id, route.params.releaseId);
}
</script>
