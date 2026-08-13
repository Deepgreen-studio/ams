<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.dashboards' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Browse dashboards
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <div v-if="store.loading && !store.templates.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 3" :key="n" class="h-48 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!store.templates.length"
      title="No templates published yet"
      description="Published dashboard templates will appear here so you can start from a curated layout."
    >
      <template #action>
        <RouterLink
          :to="{ name: 'analytics.dashboards' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Browse dashboards
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="template in store.templates"
        :key="template.uuid"
        class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ template.name }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ template.description || 'No description' }}</p>
          </div>
          <span class="rounded-md bg-brand-50 px-2 py-1 text-[11px] font-medium uppercase text-brand-700">
            template
          </span>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-2 text-xs text-slate-600">
          <div>
            <dt class="uppercase tracking-wide text-slate-400">Category</dt>
            <dd class="mt-0.5 capitalize">{{ template.category }}</dd>
          </div>
          <div>
            <dt class="uppercase tracking-wide text-slate-400">Widgets</dt>
            <dd class="mt-0.5">{{ template.widgets_count ?? 0 }}</dd>
          </div>
        </dl>
        <button
          type="button"
          class="mt-4 inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="useTemplate(template)"
        >
          Use template
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const router = useRouter();
const toast = useToast();

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

async function useTemplate(template) {
  const dashboard = await store.createFromTemplate(template.uuid, {
    name: `${template.name.replace(/ Template$/i, '')} Board`,
    visibility: 'personal',
  });

  if (dashboard?.uuid) {
    router.push({ name: 'analytics.dashboards.designer', params: { uuid: dashboard.uuid } });
  }
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  store.fetchTemplates();
});
</script>
