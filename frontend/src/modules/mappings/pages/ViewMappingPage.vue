<template>
  <div>
    <!-- <PageHeader :title="mapping?.name || 'Mapping details'" :description="subtitle">
      <template #actions>
        <RouterLink
          v-if="mapping"
          :to="{ name: 'mappings.edit', params: { id: mapping.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit builder
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          v-if="mapping"
          :to="{ name: 'mappings.edit', params: { id: mapping.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit builder
        </RouterLink>
    </Teleport>
    <MappingSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !mapping" class="h-64 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="mapping">
      <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Field map
          </h2>
          <div class="space-y-3">
            <div
              v-for="field in mapping.fields || []"
              :key="field.uuid"
              class="grid gap-2 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm md:grid-cols-[1fr_auto_1fr]"
            >
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">
                  {{ mapping.source_entity }}
                </p>
                <p class="font-medium text-slate-900">{{ field.external_field }}</p>
              </div>
              <div class="flex items-center justify-center text-slate-400">↓</div>
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Internal</p>
                <p class="font-medium text-slate-900">{{ field.internal_field }}</p>
                <p class="text-xs text-slate-500">
                  {{ field.transform_type }}
                  <span v-if="field.is_required"> · required</span>
                  <span v-if="field.default_value != null && field.default_value !== ''">
                    · default={{ field.default_value }}</span
                  >
                </p>
              </div>
            </div>
          </div>
        </section>

        <section class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
              Profile
            </h2>
            <dl class="space-y-2 text-sm">
              <div>
                <dt class="text-slate-500">Integration</dt>
                <dd class="font-medium">{{ mapping.integration?.name || '—' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Direction</dt>
                <dd class="capitalize font-medium">{{ mapping.direction }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Status</dt>
                <dd class="capitalize font-medium">{{ mapping.status }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Version</dt>
                <dd class="font-medium">{{ mapping.version }}</dd>
              </div>
            </dl>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
              Preview & validation
            </h2>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
              >Source JSON</label
            >
            <textarea
              v-model="sourceText"
              rows="8"
              class="mb-3 w-full h-12 rounded-[12px] border border-slate-300 px-3 font-mono text-xs"
            />
            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                :disabled="store.saving"
                @click="runPreview"
              >
                Preview
              </button>
              <button
                type="button"
                class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                :disabled="store.saving"
                @click="runValidate"
              >
                Validate
              </button>
            </div>
          </div>
        </section>
      </div>

      <div v-if="preview" class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between gap-3">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Mapping preview
          </h2>
          <span
            class="text-xs font-medium"
            :class="preview.result?.valid ? 'text-emerald-700' : 'text-rose-700'"
          >
            {{ preview.result?.valid ? 'Valid' : 'Invalid' }}
          </span>
        </div>
        <div
          v-if="preview.result?.errors?.length"
          class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
        >
          <p v-for="(err, idx) in preview.result.errors" :key="idx">{{ err }}</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
          <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Output</p>
            <pre
              class="max-h-80 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100"
              >{{ JSON.stringify(preview.result?.output || {}, null, 2) }}</pre>
          </div>
          <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">
              Applied transforms
            </p>
            <pre
              class="max-h-80 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100"
              >{{ JSON.stringify(preview.result?.applied || [], null, 2) }}</pre>
          </div>
        </div>
      </div>

      <div v-if="validation" class="mt-4 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
          Validation result
        </h2>
        <p class="mb-2 text-sm" :class="validation.valid ? 'text-emerald-700' : 'text-rose-700'">
          {{ validation.valid ? 'Passed' : 'Failed' }}
        </p>
        <ul v-if="validation.errors?.length" class="list-disc space-y-1 pl-5 text-sm text-rose-700">
          <li v-for="(err, idx) in validation.errors" :key="idx">{{ err }}</li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import MappingSubnav from '@/modules/mappings/components/MappingSubnav.vue';
import { useMappingsStore } from '@/modules/mappings/stores/mappings';

const route = useRoute();
const store = useMappingsStore();
const sourceText = ref('{\n"customer_name": "Ada Lovelace",\n"weight": "62.5"\n}');
const preview = ref(null);
const validation = ref(null);

const mapping = computed(() => store.currentMapping);
const subtitle = computed(() => {
  if (!mapping.value) return 'Inspect mappings, preview transforms, and validate required fields.';
  return `${mapping.value.source_entity} → ${mapping.value.target_entity || 'internal'} · v${mapping.value.version}`;
});

onMounted(async () => {
  const item = await store.fetchMapping(route.params.id);
  if (item?.sample_payload && Object.keys(item.sample_payload).length) {
    sourceText.value = JSON.stringify(item.sample_payload, null, 2);
  }
});

function parseSource() {
  try {
    return JSON.parse(sourceText.value || '{}');
  } catch {
    throw new Error('Source JSON is invalid.');
  }
}

async function runPreview() {
  try {
    preview.value = await store.previewMapping(route.params.id, { source: parseSource() });
    validation.value = null;
  } catch (err) {
    store.error = err.message || store.error;
  }
}

async function runValidate() {
  try {
    validation.value = await store.validateMapping(route.params.id, { source: parseSource() });
    preview.value = {
      result: {
        valid: validation.value.valid,
        errors: validation.value.errors,
        warnings: validation.value.warnings,
        output: validation.value.output,
        applied: [],
      },
    };
  } catch (err) {
    store.error = err.message || store.error;
  }
}
</script>
