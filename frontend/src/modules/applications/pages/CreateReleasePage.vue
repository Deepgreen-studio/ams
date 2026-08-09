<template>
  <div>
    <!-- <PageHeader
      title="Plan release"
      description="Create a release plan linked to an existing application version."
    /> -->
    <ApplicationSubnav :application-id="route.params.id" />

    <form
      class="space-y-4 rounded-xl border border-slate-200 bg-white p-6"
      @submit.prevent="onSubmit"
    >
      <div
        v-if="releasesStore.error"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ releasesStore.error }}
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
          <input v-model="form.name" type="text" class="input" required />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Version</label>
          <select v-model="form.application_version_id" class="input" required>
            <option value="" disabled>Select version</option>
            <option v-for="version in versions" :key="version.uuid" :value="version.uuid">
              {{ version.version_number }} ({{ version.status }})
            </option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Release type</label>
          <select v-model="form.release_type" class="input">
            <option value="major">Major</option>
            <option value="minor">Minor</option>
            <option value="patch">Patch</option>
            <option value="hotfix">Hotfix</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700"
            >Environment (optional)</label
          >
          <select v-model="form.environment_id" class="input">
            <option value="">None</option>
            <option v-for="env in environments" :key="env.uuid" :value="env.uuid">
              {{ env.name }} ({{ env.type }})
            </option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Scheduled at</label>
          <input v-model="form.scheduled_at" type="datetime-local" class="input" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Deployment date</label>
          <input v-model="form.deployment_date" type="datetime-local" class="input" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Plan summary</label>
          <textarea v-model="form.plan_summary" rows="3" class="input" />
        </div>
        <div class="md:col-span-2">
          <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
              v-model="form.requires_approval"
              type="checkbox"
              class="rounded border-slate-300"
            />
            Requires approval before deployment
          </label>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 p-4">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-900">Release notes</h3>
          <button
            type="button"
            class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
            @click="addNote"
          >
            Add note
          </button>
        </div>
        <div
          v-for="(note, index) in form.notes"
          :key="index"
          class="mb-3 grid gap-3 border-b border-slate-100 pb-3 last:mb-0 last:border-0 last:pb-0 md:grid-cols-2"
        >
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
              >Title</label
            >
            <input v-model="note.title" type="text" class="input" required />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
              >Audience</label
            >
            <select v-model="note.audience" class="input">
              <option value="public">Public</option>
              <option value="internal">Internal</option>
              <option value="both">Both</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
              >Content</label
            >
            <textarea v-model="note.content" rows="2" class="input" />
          </div>
          <div class="md:col-span-2 flex justify-end">
            <button
              type="button"
              class="text-xs font-medium text-rose-600 hover:underline"
              @click="form.notes.splice(index, 1)"
            >
              Remove
            </button>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="router.push({ name: 'applications.releases', params: { id: route.params.id } })"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="releasesStore.saving"
        >
          {{ releasesStore.saving ? 'Saving...' : 'Create release' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';
import { versionService } from '@/modules/applications/services/versionService';
import { environmentService } from '@/modules/applications/services/environmentService';

const route = useRoute();
const router = useRouter();
const releasesStore = useReleasesStore();
const versions = ref([]);
const environments = ref([]);

const form = reactive({
  name: '',
  application_version_id: '',
  environment_id: '',
  release_type: 'minor',
  scheduled_at: '',
  deployment_date: '',
  plan_summary: '',
  requires_approval: true,
  notes: [{ title: '', content: '', audience: 'public' }],
});

onMounted(async () => {
  try {
    const [{ data: versionData }, { data: envData }] = await Promise.all([
      versionService.list(route.params.id, { per_page: 100 }),
      environmentService.dashboard(route.params.id),
    ]);
    versions.value = versionData.data?.versions?.items ?? [];
    environments.value = envData.data?.environments ?? [];
  } catch {
    versions.value = [];
    environments.value = [];
  }
});

function addNote() {
  form.notes.push({ title: '', content: '', audience: 'public' });
}

function toIsoOrNull(value) {
  if (!value) return null;
  return new Date(value).toISOString();
}

async function onSubmit() {
  const release = await releasesStore.createRelease(route.params.id, {
    name: form.name,
    application_version_id: form.application_version_id,
    environment_id: form.environment_id || null,
    release_type: form.release_type,
    scheduled_at: toIsoOrNull(form.scheduled_at),
    deployment_date: toIsoOrNull(form.deployment_date),
    plan_summary: form.plan_summary || null,
    requires_approval: form.requires_approval,
    notes: form.notes.filter((note) => note.title),
  });

  await router.push({
    name: 'applications.releases.show',
    params: { id: route.params.id, releaseId: release.uuid },
  });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}
</style>
