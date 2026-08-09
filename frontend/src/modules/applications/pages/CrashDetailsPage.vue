<template>
  <div>
    <!-- <PageHeader
      :title="crash?.title || 'Crash details'"
      description="Stack trace, crash logs, affected version and device."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Back</RouterLink
        >
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'applications.monitoring.crashes', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Back</RouterLink
        >
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="monitoringStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ monitoringStore.error }}
    </div>
    <div
      v-if="monitoringStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ monitoringStore.successMessage }}
    </div>

    <div
      v-if="monitoringStore.loading && !crash"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="crash" class="grid gap-4 lg:grid-cols-3">
      <section class="space-y-4 lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <dl class="grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs uppercase text-slate-500">Type</dt>
              <dd class="mt-1 text-sm font-medium">{{ crash.type_label || crash.type }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Status</dt>
              <dd class="mt-1 text-sm font-medium">{{ crash.status_label || crash.status }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Version</dt>
              <dd class="mt-1 text-sm font-medium">{{ crash.version_label || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Occurrences</dt>
              <dd class="mt-1 text-sm font-medium">{{ crash.occurrence_count }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Device</dt>
              <dd class="mt-1 text-sm font-medium">
                {{ crash.device_model || '—' }} · {{ crash.device_os }}
                {{ crash.device_os_version }}
              </dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Occurred</dt>
              <dd class="mt-1 text-sm font-medium">{{ formatDate(crash.occurred_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Memory</dt>
              <dd class="mt-1 text-sm font-medium">{{ crash.memory_usage_mb ?? '—' }} MB</dd>
            </div>
            <div>
              <dt class="text-xs uppercase text-slate-500">Battery</dt>
              <dd class="mt-1 text-sm font-medium">{{ crash.battery_level ?? '—' }}%</dd>
            </div>
          </dl>
          <p v-if="crash.message" class="mt-4 text-sm text-slate-600">{{ crash.message }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <h3 class="text-sm font-semibold text-slate-900">Stack trace</h3>
          <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-emerald-200">{{
            crash.stack_trace || 'No stack trace'
          }}</pre>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <h3 class="text-sm font-semibold text-slate-900">Crash log</h3>
          <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-50 p-4 text-xs text-slate-700">{{
            crash.crash_log || 'No crash log'
          }}</pre>
        </div>
      </section>

      <aside class="rounded-xl border border-slate-200 bg-white p-5">
        <h3 class="text-sm font-semibold text-slate-900">Update status</h3>
        <select
          v-model="status"
          class="mt-3 w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="open">Open</option>
          <option value="investigating">Investigating</option>
          <option value="resolved">Resolved</option>
          <option value="ignored">Ignored</option>
        </select>
        <button
          type="button"
          class="mt-3 w-full rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="monitoringStore.saving"
          @click="saveStatus"
        >
          Save
        </button>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useMonitoringStore } from '@/modules/applications/stores/monitoring';

const route = useRoute();
const monitoringStore = useMonitoringStore();
const status = ref('open');
const crash = computed(() => monitoringStore.currentCrash);

onMounted(() => monitoringStore.fetchCrash(route.params.id, route.params.crashId));
watch(
  crash,
  (value) => {
    if (value) status.value = value.status;
  },
  { immediate: true },
);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function saveStatus() {
  await monitoringStore.updateCrash(route.params.id, route.params.crashId, {
    status: status.value,
  });
}
</script>
