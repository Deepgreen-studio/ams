<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{
          name: 'applications.releases.show',
          params: { id: route.params.id, releaseId: route.params.releaseId },
        }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to details
      </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="releasesStore.loading && !release"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="release" class="grid gap-4 lg:grid-cols-3">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8 lg:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
              {{ release.name }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
              Version {{ release.version_label || '—' }} ·
              {{ release.release_type_label || release.release_type || '—' }}
            </p>
          </div>
          <ReleaseStatusBadge :status="release.status" :label="release.status_label" />
        </div>

        <div class="mt-6 rounded-[12px] bg-zinc-50 px-4 py-3.5">
          <p class="text-xs font-medium text-zinc-500">Plan summary</p>
          <p class="mt-1 text-sm font-medium text-slate-800">
            {{ release.plan_summary || 'No plan summary provided.' }}
          </p>
        </div>

        <div class="mt-6">
          <h3 class="text-sm font-semibold text-slate-900">Release notes</h3>
          <div v-if="!(release.notes || []).length" class="mt-3 text-sm text-slate-500">
            No notes.
          </div>
          <div v-else class="mt-3 space-y-3">
            <article
              v-for="note in release.notes"
              :key="note.uuid"
              class="rounded-[12px] bg-zinc-50 px-4 py-3.5 ring-1 ring-zinc-100"
            >
              <div class="flex items-center justify-between gap-2">
                <p class="font-medium text-slate-900">{{ note.title }}</p>
                <span
                  v-if="note.audience"
                  class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-2.5 py-0.5 text-xs font-medium text-slate-600"
                >
                  {{ note.audience }}
                </span>
              </div>
              <p class="mt-1.5 whitespace-pre-wrap text-sm text-slate-600">
                {{ note.content || '—' }}
              </p>
            </article>
          </div>
        </div>
      </section>

      <aside class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-6">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current approval</p>
        <div class="mt-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
            :class="approvalClasses"
          >
            <span class="h-1.5 w-1.5 rounded-full" :class="approvalDot" />
            {{ release.approval_status_label || approvalLabel }}
          </span>
        </div>

        <div class="mt-5">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Notes</label>
          <textarea
            v-model="notes"
            rows="4"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            placeholder="Approval or rejection notes"
          />
        </div>

        <div class="mt-5 flex flex-col gap-2">
          <button
            type="button"
            class="rounded-[12px] bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
            :disabled="releasesStore.saving || release.approval_status !== 'pending'"
            @click="onApprove"
          >
            {{ releasesStore.saving ? 'Processing...' : 'Approve release' }}
          </button>
          <button
            type="button"
            class="rounded-[12px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-60"
            :disabled="releasesStore.saving || release.approval_status !== 'pending'"
            @click="onReject"
          >
            Reject release
          </button>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import ReleaseStatusBadge from '@/modules/applications/components/ReleaseStatusBadge.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const releasesStore = useReleasesStore();
const toast = useToast();
const notes = ref('');
const release = computed(() => releasesStore.currentRelease);

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

async function onApprove() {
  try {
    await releasesStore.approveRelease(route.params.id, route.params.releaseId, {
      approval_notes: notes.value || null,
    });
  } catch {
    // Toast handled by watcher.
  }
}

async function onReject() {
  if (!notes.value.trim()) {
    toast.error('Rejection notes are required.', 'Validation Failed');
    return;
  }
  try {
    await releasesStore.rejectRelease(route.params.id, route.params.releaseId, {
      approval_notes: notes.value,
    });
  } catch {
    // Toast handled by watcher.
  }
}
</script>
