<template>
  <div>
    <PageHeader
      :title="configuration?.name || 'Edit configuration'"
      description="Update validated JSON configuration and publish status."
    >
      <template #actions>
        <RouterLink
          v-if="configuration"
          :to="{
            name: 'applications.configurations.history',
            params: { id: route.params.id, configurationId: configuration.uuid },
          }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          History
        </RouterLink>
        <RouterLink
          v-if="configuration?.type === 'feature_flags'"
          :to="{
            name: 'applications.configurations.flags',
            params: { id: route.params.id, configurationId: configuration.uuid },
          }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Feature flags
        </RouterLink>
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="configurationsStore.loading && !configuration"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />

    <form
      v-else
      class="space-y-4 rounded-xl border border-slate-200 bg-white p-6"
      @submit.prevent="onSubmit"
    >
      <div
        v-if="configurationsStore.error"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ configurationsStore.error }}
      </div>
      <div
        v-if="configurationsStore.successMessage"
        class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
      >
        {{ configurationsStore.successMessage }}
      </div>

      <div class="grid gap-4 md:grid-cols-2">
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
          <label class="mb-1 block text-sm font-medium text-slate-700">Change summary</label>
          <input
            v-model="form.change_summary"
            type="text"
            class="input"
            placeholder="Describe this change for history"
          />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
          <textarea v-model="form.description" rows="2" class="input" />
        </div>
      </div>

      <JsonEditor
        v-model="jsonText"
        :hint="
          configuration?.is_sensitive
            ? 'Sensitive values are masked. Leave ******** to keep existing secrets.'
            : ''
        "
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
          Back
        </button>
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="configurationsStore.saving"
        >
          {{ configurationsStore.saving ? 'Saving...' : 'Save configuration' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import JsonEditor from '@/modules/applications/components/JsonEditor.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';

const route = useRoute();
const router = useRouter();
const configurationsStore = useConfigurationsStore();
const jsonText = ref('{}');
const jsonError = ref('');
const form = reactive({
  name: '',
  description: '',
  status: 'draft',
  change_summary: '',
});

const configuration = computed(() => configurationsStore.currentConfiguration);

onMounted(async () => {
  await configurationsStore.fetchConfiguration(route.params.id, route.params.configurationId);
});

watch(
  configuration,
  (value) => {
    if (!value) return;
    form.name = value.name || '';
    form.description = value.description || '';
    form.status = value.status || 'draft';
    jsonText.value = JSON.stringify(value.payload || {}, null, 2);
  },
  { immediate: true },
);

async function onValidate() {
  jsonError.value = '';
  let payload;
  try {
    payload = JSON.parse(jsonText.value);
  } catch {
    jsonError.value = 'Invalid JSON syntax.';
    return;
  }
  await configurationsStore.validateConfiguration(route.params.id, {
    type: configuration.value.type,
    payload,
  });
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

  await configurationsStore.updateConfiguration(route.params.id, route.params.configurationId, {
    name: form.name,
    description: form.description || null,
    status: form.status,
    change_summary: form.change_summary || 'Configuration updated',
    payload,
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
