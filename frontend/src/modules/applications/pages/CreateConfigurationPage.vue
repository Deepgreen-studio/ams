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
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Type</label>
          <SelectBox
            v-model="form.type"
            size="lg"
            :options="typeSelectOptions"
            @change="onTypeChange"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700"
            >Environment (optional)</label
          >
          <SelectBox v-model="form.environment_id" size="lg" :options="environmentOptions" />
        </div>
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
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
          <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
        <div class="md:col-span-2">
          <JsonEditor
            v-model="jsonText"
            label="Initial JSON payload"
            :validating="configurationsStore.saving"
            :validation="configurationsStore.validationResult"
            :error="jsonError"
            @validate="onValidate"
          />
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="
            router.push({ name: 'applications.configurations', params: { id: route.params.id } })
          "
        >
          Cancel
        </button>
        <button
          type="submit"
          class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
          :disabled="configurationsStore.saving"
        >
          {{ configurationsStore.saving ? 'Saving...' : 'Create configuration' }}
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
import JsonEditor from '@/modules/applications/components/JsonEditor.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';
import { configurationService } from '@/modules/applications/services/configurationService';
import { environmentService } from '@/modules/applications/services/environmentService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const router = useRouter();
const configurationsStore = useConfigurationsStore();
const toast = useToast();
const environments = ref([]);
const catalog = ref({});
const jsonText = ref('{\n"flags": []\n}');
const jsonError = ref('');

const form = reactive({
  type: 'feature_flags',
  environment_id: '',
  name: 'Feature Flags',
  description: '',
  status: 'draft',
});

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'archived', label: 'Archived' },
];

const typeSelectOptions = computed(() =>
  Object.entries(catalog.value || {}).map(([type, meta]) => ({
    value: type,
    label: meta.label || type,
  })),
);

const environmentOptions = computed(() => [
  { value: '', label: 'Application-wide' },
  ...environments.value.map((env) => ({
    value: env.uuid,
    label: `${env.name} (${env.type})`,
  })),
]);

watch(
  () => configurationsStore.error,
  (message) => {
    if (message) toast.error(message, 'Validation Failed');
  },
);

watch(
  () => configurationsStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

onMounted(async () => {
  try {
    const [{ data: catalogData }, { data: envData }] = await Promise.all([
      configurationService.catalog(route.params.id),
      environmentService.dashboard(route.params.id),
    ]);
    catalog.value = catalogData.data?.catalog ?? {};
    environments.value = envData.data?.environments ?? [];
    onTypeChange();
  } catch {
    catalog.value = {};
  }
});

function onTypeChange() {
  const meta = catalog.value[form.type];
  form.name = meta?.label || form.type;
  jsonText.value = JSON.stringify(meta?.default_payload || {}, null, 2);
  configurationsStore.validationResult = null;
}

async function onValidate() {
  jsonError.value = '';
  let payload;
  try {
    payload = JSON.parse(jsonText.value);
  } catch {
    jsonError.value = 'Invalid JSON syntax.';
    toast.error('Invalid JSON syntax.', 'Validation Failed');
    return;
  }
  try {
    await configurationsStore.validateConfiguration(route.params.id, {
      type: form.type,
      payload,
    });
    if (configurationsStore.validationResult?.valid) {
      toast.success('Payload is valid');
    }
  } catch {
    // Toast handled by watcher.
  }
}

async function onSubmit() {
  jsonError.value = '';
  let payload;
  try {
    payload = JSON.parse(jsonText.value);
  } catch {
    jsonError.value = 'Invalid JSON syntax.';
    toast.error('Invalid JSON syntax.', 'Validation Failed');
    return;
  }

  try {
    const configuration = await configurationsStore.createConfiguration(route.params.id, {
      type: form.type,
      environment_id: form.environment_id || null,
      name: form.name,
      description: form.description || null,
      status: form.status,
      payload,
    });

    await router.push({
      name:
        form.type === 'feature_flags'
          ? 'applications.configurations.flags'
          : 'applications.configurations.edit',
      params: { id: route.params.id, configurationId: configuration.uuid },
    });
  } catch {
    // Toast handled by watcher.
  }
}
</script>
