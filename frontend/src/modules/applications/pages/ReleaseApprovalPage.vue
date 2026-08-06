<template>
  <div>
    <PageHeader
      title="Release approval"
      description="Review release plan and approve or reject deployment."
    >
      <template #actions>
        <RouterLink
          :to="{
            name: 'applications.releases.show',
            params: { id: route.params.id, releaseId: route.params.releaseId },
          }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to details
        </RouterLink>
      </template>
    </PageHeader>

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
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="release" class="grid gap-4 lg:grid-cols-3">
      <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-900">{{ release.name }}</h2>
        <p class="mt-1 text-sm text-slate-500">
          Version {{ release.version_label }} ·
          {{ release.release_type_label || release.release_type }}
        </p>
        <p class="mt-4 text-sm text-slate-700">
          {{ release.plan_summary || 'No plan summary provided.' }}
        </p>

        <div class="mt-6 space-y-3">
          <h3 class="text-sm font-semibold text-slate-900">Release notes</h3>
          <article
            v-for="note in release.notes || []"
            :key="note.uuid"
            class="rounded-lg border border-slate-100 p-3"
          >
            <p class="font-medium text-slate-900">{{ note.title }}</p>
            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ note.content || '—' }}</p>
          </article>
          <p v-if="!(release.notes || []).length" class="text-sm text-slate-500">No notes.</p>
        </div>
      </section>

      <aside class="rounded-xl border border-slate-200 bg-white p-6">
        <p class="text-xs uppercase tracking-wide text-slate-500">Current approval</p>
        <p class="mt-1 text-base font-semibold text-slate-900">
          {{ release.approval_status_label || release.approval_status }}
        </p>

        <div class="mt-4">
          <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
          <textarea
            v-model="notes"
            rows="4"
            class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            placeholder="Approval or rejection notes"
          />
        </div>

        <div class="mt-4 flex flex-col gap-2">
          <button
            type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
            :disabled="releasesStore.saving || release.approval_status !== 'pending'"
            @click="onApprove"
          >
            Approve release
          </button>
          <button
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
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
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';

const route = useRoute();
const releasesStore = useReleasesStore();
const notes = ref('');
const release = computed(() => releasesStore.currentRelease);

onMounted(() => {
  releasesStore.fetchRelease(route.params.id, route.params.releaseId);
});

async function onApprove() {
  await releasesStore.approveRelease(route.params.id, route.params.releaseId, {
    approval_notes: notes.value || null,
  });
}

async function onReject() {
  if (!notes.value.trim()) {
    releasesStore.error = 'Rejection notes are required.';
    return;
  }
  await releasesStore.rejectRelease(route.params.id, route.params.releaseId, {
    approval_notes: notes.value,
  });
}
</script>
