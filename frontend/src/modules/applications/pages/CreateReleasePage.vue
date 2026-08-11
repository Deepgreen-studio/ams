<template>
  <div>
    <ApplicationSubnav :application-id="route.params.id" />

    <form
      class="space-y-8 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8"
      novalidate
      @submit.prevent="onSubmit"
    >
      <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Version</label>
          <SelectBox
            v-model="form.application_version_id"
            size="lg"
            :options="versionOptions"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Release type</label>
          <SelectBox v-model="form.release_type" size="lg" :options="releaseTypeOptions" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700"
            >Environment (optional)</label
          >
          <SelectBox v-model="form.environment_id" size="lg" :options="environmentOptions" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Scheduled at</label>
          <input
            v-model="form.scheduled_at"
            type="datetime-local"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Deployment date</label>
          <input
            v-model="form.deployment_date"
            type="datetime-local"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Plan summary</label>
          <textarea
            v-model="form.plan_summary"
            rows="3"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div class="md:col-span-2">
          <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-slate-700">
            <input
              v-model="form.requires_approval"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            />
            Requires approval before deployment
          </label>
        </div>
      </div>

      <div class="rounded-[12px] bg-zinc-50/70 p-4 ring-1 ring-zinc-100 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-900">Release notes</h3>
          <button
            type="button"
            class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50"
            @click="addNote"
          >
            Add note
          </button>
        </div>

        <div v-if="!form.notes.length" class="text-sm text-slate-500">
          No release notes yet. Add one if needed.
        </div>

        <div
          v-for="(note, index) in form.notes"
          :key="index"
          class="mb-4 rounded-[12px] bg-white p-4 ring-1 ring-zinc-100 last:mb-0"
        >
          <div class="grid gap-x-6 gap-y-4 md:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
              <input
                v-model="note.title"
                type="text"
                required
                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Audience</label>
              <SelectBox v-model="note.audience" size="lg" :options="audienceOptions" />
            </div>
            <div class="md:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-slate-700">Content</label>
              <textarea
                v-model="note.content"
                rows="3"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
              />
            </div>
          </div>
          <div class="mt-3 flex justify-end">
            <button
              type="button"
              class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50"
              @click="form.notes.splice(index, 1)"
            >
              Remove
            </button>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="router.push({ name: 'applications.releases', params: { id: route.params.id } })"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
          :disabled="releasesStore.saving"
        >
          {{ releasesStore.saving ? 'Saving...' : 'Create release' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';
import { versionService } from '@/modules/applications/services/versionService';
import { environmentService } from '@/modules/applications/services/environmentService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const router = useRouter();
const releasesStore = useReleasesStore();
const toast = useToast();
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

const releaseTypeOptions = [
  { value: 'major', label: 'Major' },
  { value: 'minor', label: 'Minor' },
  { value: 'patch', label: 'Patch' },
  { value: 'hotfix', label: 'Hotfix' },
  { value: 'custom', label: 'Custom' },
];

const audienceOptions = [
  { value: 'public', label: 'Public' },
  { value: 'internal', label: 'Internal' },
  { value: 'both', label: 'Both' },
];

const versionOptions = computed(() => [
  { value: '', label: 'Select version' },
  ...versions.value.map((version) => ({
    value: version.uuid,
    label: `${version.version_number} (${version.status})`,
  })),
]);

const environmentOptions = computed(() => [
  { value: '', label: 'None' },
  ...environments.value.map((env) => ({
    value: env.uuid,
    label: `${env.name} (${env.type})`,
  })),
]);

watch(
  () => releasesStore.error,
  (message) => {
    if (message) toast.error(message, 'Validation Failed');
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
    const [{ data: versionData }, { data: envData }] = await Promise.all([
      versionService.list(route.params.id, { per_page: 100 }),
      environmentService.dashboard(route.params.id),
    ]);
    versions.value = versionData.data?.versions?.items ?? [];
    environments.value = envData.data?.environments ?? [];
  } catch {
    versions.value = [];
    environments.value = [];
    toast.error('Unable to load versions or environments');
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
  if (!form.application_version_id) {
    toast.error('Please select a version.', 'Validation Failed');
    return;
  }

  try {
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
  } catch {
    // Toast handled by watcher.
  }
}
</script>
