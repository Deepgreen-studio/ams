<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div v-if="configuration" class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{
            name: 'applications.configurations.history',
            params: { id: route.params.id, configurationId: configuration.uuid },
          }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          History
        </RouterLink>
        <RouterLink
          v-if="configuration.type === 'feature_flags'"
          :to="{
            name: 'applications.configurations.flags',
            params: { id: route.params.id, configurationId: configuration.uuid },
          }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Feature flags
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="configurationsStore.loading && !configuration"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />

    <form
      v-else
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
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
          <SelectBox v-model="form.status" size="lg" :options="statusOptions" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Change summary</label>
          <input
            v-model="form.change_summary"
            type="text"
            placeholder="Describe this change for history"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
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
          Back
        </button>
        <button
          type="submit"
          class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
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
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import JsonEditor from '@/modules/applications/components/JsonEditor.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const router = useRouter();
const configurationsStore = useConfigurationsStore();
const toast = useToast();
const jsonText = ref('{}');
const jsonError = ref('');
const form = reactive({
  name: '',
  description: '',
  status: 'draft',
  change_summary: '',
});

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'archived', label: 'Archived' },
];

const configuration = computed(() => configurationsStore.currentConfiguration);

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
    toast.error('Invalid JSON syntax.', 'Validation Failed');
    return;
  }
  try {
    await configurationsStore.validateConfiguration(route.params.id, {
      type: configuration.value.type,
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
    await configurationsStore.updateConfiguration(route.params.id, route.params.configurationId, {
      name: form.name,
      description: form.description || null,
      status: form.status,
      change_summary: form.change_summary || 'Configuration updated',
      payload,
    });
  } catch {
    // Toast handled by watcher.
  }
}
</script>
