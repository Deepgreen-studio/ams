<template>
  <div>
    <!-- <PageHeader
      :title="environment?.name || 'Environment details'"
      description="Environment configuration, health status, and switch controls."
    >
      <template #actions>
        <template v-if="environment">
          <button
            v-if="!environment.is_current"
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            :disabled="environmentsStore.saving"
            @click="switchTo"
          >
            Switch to this
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            :disabled="environmentsStore.saving"
            @click="healthCheck"
          >
            Run health check
          </button>
          <RouterLink
            :to="{
              name: 'applications.environments.edit',
              params: { id: route.params.id, environmentId: environment.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <div v-if="environment" class="flex flex-wrap items-center justify-end gap-2">
        <button
          v-if="!environment.is_current"
          type="button"
          class="rounded-[12px] border border-zinc-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          :disabled="environmentsStore.saving"
          @click="switchTo"
        >
          Switch to this
        </button>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          :disabled="environmentsStore.saving"
          @click="healthCheck"
        >
          Run health check
        </button>
        <RouterLink
          :to="{
            name: 'applications.environments.edit',
            params: { id: route.params.id, environmentId: environment.uuid },
          }"
          class="rounded-[12px] border border-zinc-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Edit
        </RouterLink>
        <button
          type="button"
          class="rounded-[12px] bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
          @click="showDelete = true"
        >
          Delete
        </button>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="environmentsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ environmentsStore.error }}
    </div>
    <div
      v-if="environmentsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ environmentsStore.successMessage }}
    </div>

    <div
      v-if="environmentsStore.loading && !environment"
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="environment" class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-xl font-semibold text-slate-900">{{ environment.name }}</h2>
              <span
                v-if="environment.is_current"
                class="rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-600/20"
                >Current</span
              >
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

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">API URL</dt>
            <dd class="mt-1 break-all text-sm text-slate-900">{{ environment.api_url || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Web URL</dt>
            <dd class="mt-1 break-all text-sm text-slate-900">{{ environment.web_url || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
              Last health check
            </dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ formatDate(environment.last_health_check) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Updated by</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ environment.updater?.full_name || environment.creator?.full_name || '—' }}
            </dd>
          </div>
        </dl>
      </div>

      <div
        v-if="environmentsStore.lastHealthCheck"
        class="rounded-xl border border-slate-200 bg-white p-5"
      >
        <h3 class="text-sm font-semibold text-slate-800">Latest health check</h3>
        <dl class="mt-3 grid gap-3 sm:grid-cols-4 text-sm">
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Result</dt>
            <dd class="mt-1">
              <EnvironmentHealthBadge :status="environmentsStore.lastHealthCheck.health_status" />
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Status code</dt>
            <dd class="mt-1 text-slate-900">
              {{ environmentsStore.lastHealthCheck.status_code ?? '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Latency</dt>
            <dd class="mt-1 text-slate-900">
              {{ environmentsStore.lastHealthCheck.latency_ms ?? '—' }} ms
            </dd>
          </div>
          <div class="sm:col-span-1">
            <dt class="text-xs uppercase tracking-wide text-slate-500">Message</dt>
            <dd class="mt-1 text-slate-900">
              {{ environmentsStore.lastHealthCheck.message || '—' }}
            </dd>
          </div>
        </dl>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h3 class="text-sm font-semibold text-slate-800">Environment variables</h3>
        <p class="mt-1 text-xs text-slate-500">
          Secret values are encrypted and always masked in responses.
        </p>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-600">Key</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-600">Value</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in environment.variables || []" :key="item.key">
                <td class="px-4 py-3 font-medium text-slate-800">{{ item.key }}</td>
                <td class="px-4 py-3 font-mono text-slate-600">{{ item.masked_value || '—' }}</td>
              </tr>
              <tr v-if="!(environment.variables || []).length">
                <td colspan="2" class="px-4 py-6 text-center text-slate-500">
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
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import EnvironmentHealthBadge from '@/modules/applications/components/EnvironmentHealthBadge.vue';
import { useEnvironmentsStore } from '@/modules/applications/stores/environments';

const route = useRoute();
const router = useRouter();
const environmentsStore = useEnvironmentsStore();
const showDelete = ref(false);

const environment = computed(() => environmentsStore.selectedEnvironment);

onMounted(() => {
  environmentsStore.fetchEnvironment(route.params.id, route.params.environmentId);
});

function formatDate(value) {
  if (!value) return 'Never';
  return new Date(value).toLocaleString();
}

async function switchTo() {
  await environmentsStore.switchEnvironment(route.params.id, route.params.environmentId);
  await environmentsStore.fetchEnvironment(route.params.id, route.params.environmentId);
}

async function healthCheck() {
  await environmentsStore.runHealthCheck(route.params.id, route.params.environmentId);
  await environmentsStore.fetchEnvironment(route.params.id, route.params.environmentId);
}

async function confirmDelete() {
  await environmentsStore.deleteEnvironment(route.params.id, route.params.environmentId);
  showDelete.value = false;
  await router.push({ name: 'applications.environments', params: { id: route.params.id } });
}
</script>
