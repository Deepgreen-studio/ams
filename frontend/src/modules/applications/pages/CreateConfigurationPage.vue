<template>
  <div>
    <!-- <PageHeader
      title="Create configuration"
      description="Initialize a typed JSON configuration for this application scope."
    /> -->
    <ApplicationSubnav :application-id="route.params.id" />

    <form
      class="space-y-4 rounded-xl border border-slate-200 bg-white p-6"
      @submit.prevent="onSubmit"
    >
      <div
        v-if="configurationsStore.error"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ configurationsStore.error }}
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
          <select v-model="form.type" class="input" required @change="onTypeChange">
            <option v-for="(meta, type) in typeOptions" :key="type" :value="type">
              {{ meta.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700"
            >Environment (optional)</label
          >
          <select v-model="form.environment_id" class="input">
            <option value="">Application-wide</option>
            <option v-for="env in environments" :key="env.uuid" :value="env.uuid">
              {{ env.name }} ({{ env.type }})
            </option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
          <input v-model="form.name" type="text" class="input" required />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
          <select v-model="form.status" class="input">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
          <textarea v-model="form.description" rows="2" class="input" />
        </div>
      </div>

      <JsonEditor
        v-model="jsonText"
        label="Initial JSON payload"
        :validating="configurationsStore.saving"
        :validation="configurationsStore.validationResult"
        :error="jsonError"
        @validate="onValidate"
      />

      <div class="flex justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="
            router.push({ name: 'applications.configurations', params: { id: route.params.id } })
          "
        >
          Cancel
        </button>
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="configurationsStore.saving"
        >
          {{ configurationsStore.saving ? 'Saving...' : 'Create configuration' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import JsonEditor from '@/modules/applications/components/JsonEditor.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';
import { configurationService } from '@/modules/applications/services/configurationService';
import { environmentService } from '@/modules/applications/services/environmentService';

const route = useRoute();
const router = useRouter();
const configurationsStore = useConfigurationsStore();
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

const typeOptions = computed(() => catalog.value || {});

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
    return;
  }
  await configurationsStore.validateConfiguration(route.params.id, { type: form.type, payload });
}

async function onSubmit() {
  jsonError.value = '';
  let payload;
  try {
    payload = JSON.parse(jsonText.value);
  } catch {
    jsonError.value = 'Invalid JSON syntax.';
    return;
  }

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
