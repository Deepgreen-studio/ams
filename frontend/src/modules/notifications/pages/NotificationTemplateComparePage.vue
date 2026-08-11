<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'notifications.templates.versions', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to versions
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div class="mb-4 flex flex-wrap gap-2 rounded-[12px] bg-white p-4 ring-1 ring-zinc-100">
      <select v-model="from" class="rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700">
        <option v-for="item in store.templateVersions" :key="`from-${item.uuid}`" :value="item.uuid">
          v{{ item.version }}
        </option>
      </select>
      <select v-model="to" class="rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700">
        <option v-for="item in store.templateVersions" :key="`to-${item.uuid}`" :value="item.uuid">
          v{{ item.version }}
        </option>
      </select>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="compare"
      >
        Compare
      </button>
    </div>

    <div
      v-if="error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div v-if="result" class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <table class="min-w-full divide-y divide-zinc-100 text-sm">
        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-5 py-3.5">Field</th>
            <th class="px-5 py-3.5">From v{{ result.from.version }}</th>
            <th class="px-5 py-3.5">To v{{ result.to.version }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
          <tr v-if="!changedFields.length">
            <td colspan="3" class="px-5 py-12 text-center text-slate-500">No differences.</td>
          </tr>
          <tr v-for="field in changedFields" :key="field" class="hover:bg-zinc-50/80">
            <td class="px-5 py-4 font-medium text-slate-900">{{ field }}</td>
            <td class="px-5 py-4 text-slate-600">
              <pre class="whitespace-pre-wrap font-sans text-xs">{{ formatValue(result.comparison.changes[field].from) }}</pre>
            </td>
            <td class="px-5 py-4 text-slate-600">
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
