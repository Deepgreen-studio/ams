<template>
  <div>
    <!-- <PageHeader title="Compare Template Versions" description="Diff field changes between two immutable snapshots.">
      <template #actions>
        <RouterLink
          :to="{ name: 'notifications.templates.versions', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to versions
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'notifications.templates.versions', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to versions
        </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div class="mb-4 flex flex-wrap gap-2">
      <select v-model="from" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option v-for="item in store.templateVersions" :key="`from-${item.uuid}`" :value="item.uuid">
          v{{ item.version }}
        </option>
      </select>
      <select v-model="to" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option v-for="item in store.templateVersions" :key="`to-${item.uuid}`" :value="item.uuid">
          v{{ item.version }}
        </option>
      </select>
      <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white" @click="compare">
        Compare
      </button>
    </div>

    <p v-if="error" class="mb-4 text-sm text-rose-600">{{ error }}</p>

    <div v-if="result" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Field</th>
            <th class="px-4 py-3">From v{{ result.from.version }}</th>
            <th class="px-4 py-3">To v{{ result.to.version }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="!changedFields.length">
            <td colspan="3" class="px-4 py-8 text-center text-slate-500">No differences.</td>
          </tr>
          <tr v-for="field in changedFields" :key="field">
            <td class="px-4 py-3 font-medium text-slate-900">{{ field }}</td>
            <td class="px-4 py-3 text-slate-600">
              <pre class="whitespace-pre-wrap font-sans text-xs">{{ formatValue(result.comparison.changes[field].from) }}</pre>
            </td>
            <td class="px-4 py-3 text-slate-600">
              <pre class="whitespace-pre-wrap font-sans text-xs">{{ formatValue(result.comparison.changes[field].to) }}</pre>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const route = useRoute();
const from = ref('');
const to = ref('');
const result = ref(null);
const error = ref('');

const changedFields = computed(() => result.value?.comparison?.changed_fields || []);

onMounted(async () => {
  await store.fetchTemplateVersions(route.params.id);
  if (store.templateVersions.length >= 2) {
    to.value = store.templateVersions[0].uuid;
    from.value = store.templateVersions[1].uuid;
    await compare();
  } else if (store.templateVersions.length === 1) {
    from.value = store.templateVersions[0].uuid;
    to.value = store.templateVersions[0].uuid;
  }
});

async function compare() {
  error.value = '';
  try {
    result.value = await store.compareTemplateVersions(route.params.id, from.value, to.value);
  } catch (err) {
    error.value = err?.response?.data?.message || store.error || 'Compare failed';
  }
}

function formatValue(value) {
  if (value == null) return '—';
  if (typeof value === 'object') return JSON.stringify(value, null, 2);
  return String(value);
}
</script>
