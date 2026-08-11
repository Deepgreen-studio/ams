<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div v-if="environment" class="flex flex-wrap items-center justify-end gap-2">
        <button
          v-if="!environment.is_current"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="environmentsStore.saving"
          @click="switchTo"
        >
          <ArrowPathIcon class="h-4 w-4 text-slate-500" />
          Switch to this
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="environmentsStore.saving"
          @click="healthCheck"
        >
          <HeartIcon class="h-4 w-4 text-slate-500" />
          Run health check
        </button>
        <RouterLink
          :to="{
            name: 'applications.environments.edit',
            params: { id: route.params.id, environmentId: environment.uuid },
          }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Delete
        </button>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="environmentsStore.loading && !environment"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="environment" class="space-y-6">
      <div
        class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 transition"
        :class="environment.is_current ? 'ring-brand-600' : 'ring-zinc-100'"
      >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
                {{ environment.name }}
              </h2>
              <span
                v-if="environment.is_current"
                class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700"
              >
                Current
              </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
              {{ environment.type_label || environment.type }} · {{ environment.slug }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <EnvironmentHealthBadge :status="environment.status" kind="status" />
            <EnvironmentHealthBadge :status="environment.health_status" />
          </div>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <p class="text-xs font-medium text-zinc-500">API URL</p>
            <p class="mt-1 break-all text-sm font-semibold text-slate-900">
              {{ environment.api_url || '—' }}
            </p>
          </div>
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <p class="text-xs font-medium text-zinc-500">Web URL</p>
            <p class="mt-1 break-all text-sm font-semibold text-slate-900">
              {{ environment.web_url || '—' }}
            </p>
          </div>
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <p class="text-xs font-medium text-zinc-500">Last health check</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">
              {{ formatDateTime(environment.last_health_check) }}
            </p>
          </div>
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <p class="text-xs font-medium text-zinc-500">Updated by</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">
              {{ environment.updater?.full_name || environment.creator?.full_name || '—' }}
            </p>
          </div>
        </div>
      </div>

      <div
        v-if="environmentsStore.lastHealthCheck"
        class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100"
      >
        <h3 class="text-base font-semibold text-slate-900">Latest health check</h3>
        <p class="mt-1 text-sm text-slate-500">Most recent probe result for this environment.</p>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <p class="text-xs font-medium text-zinc-500">Result</p>
            <div class="mt-2">
              <EnvironmentHealthBadge :status="environmentsStore.lastHealthCheck.health_status" />
            </div>
          </div>
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <p class="text-xs font-medium text-zinc-500">Status code</p>
            <p class="mt-1.5 text-sm font-semibold text-slate-900">
              {{ environmentsStore.lastHealthCheck.status_code ?? '—' }}
            </p>
          </div>
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <p class="text-xs font-medium text-zinc-500">Latency</p>
            <p class="mt-1.5 text-sm font-semibold text-slate-900">
              {{ environmentsStore.lastHealthCheck.latency_ms ?? '—' }} ms
            </p>
          </div>
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 sm:col-span-2 xl:col-span-1">
            <p class="text-xs font-medium text-zinc-500">Message</p>
            <p class="mt-1.5 break-words text-sm font-medium text-slate-900">
              {{ environmentsStore.lastHealthCheck.message || '—' }}
            </p>
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
          <h3 class="text-base font-semibold text-slate-900">Environment variables</h3>
          <p class="mt-1 text-sm text-slate-500">
            Secret values are encrypted and always masked in responses.
          </p>
        </div>

        <div class="overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Key</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Value</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in environment.variables || []"
                :key="item.key"
                class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
              >
                <td class="px-5 py-4 font-semibold text-slate-900">{{ item.key }}</td>
                <td class="px-5 py-4 font-mono text-slate-600">{{ item.masked_value || '—' }}</td>
              </tr>
              <tr v-if="!(environment.variables || []).length">
                <td colspan="2" class="px-5 py-10 text-center text-slate-500">
                  No variables configured.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete environment"
      :message="`Soft delete ${environment?.name || 'this environment'}?`"
      confirm-label="Delete"
      :loading="environmentsStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import {
  ArrowPathIcon,
  HeartIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import EnvironmentHealthBadge from '@/modules/applications/components/EnvironmentHealthBadge.vue';
import { useEnvironmentsStore } from '@/modules/applications/stores/environments';

const route = useRoute();
const router = useRouter();
const environmentsStore = useEnvironmentsStore();
const toast = useToast();
const showDelete = ref(false);

const environment = computed(() => environmentsStore.selectedEnvironment);

watch(
  () => environmentsStore.error,
  (message) => {
    if (message) toast.error(message, 'Error');
  },
);

watch(
  () => environmentsStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

onMounted(() => {
  environmentsStore.fetchEnvironment(route.params.id, route.params.environmentId);
});

function formatDateTime(value) {
  if (!value) return 'Never';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleString();
}

async function switchTo() {
  try {
    await environmentsStore.switchEnvironment(route.params.id, route.params.environmentId);
    await environmentsStore.fetchEnvironment(route.params.id, route.params.environmentId);
  } catch {
    // Toast handled by watcher.
  }
}

async function healthCheck() {
  try {
    await environmentsStore.runHealthCheck(route.params.id, route.params.environmentId);
    await environmentsStore.fetchEnvironment(route.params.id, route.params.environmentId);
  } catch {
    // Toast handled by watcher.
  }
}

async function confirmDelete() {
  try {
    await environmentsStore.deleteEnvironment(route.params.id, route.params.environmentId);
    showDelete.value = false;
    await router.push({ name: 'applications.environments', params: { id: route.params.id } });
  } catch {
    // Toast handled by watcher.
  }
}
</script>
