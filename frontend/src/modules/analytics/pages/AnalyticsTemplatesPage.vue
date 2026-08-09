<template>
  <div>
    <!-- <PageHeader
      title="Dashboard Templates"
      description="Start from curated templates for business, operations, and company analytics boards."
    /> -->

    <AnalyticsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="template in store.templates"
        :key="template.uuid"
        class="rounded-xl border border-slate-200 bg-white p-5"
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
          class="mt-4 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="useTemplate(template)"
        >
          Use template
        </button>
      </div>

      <div
        v-if="!store.loading && !store.templates.length"
        class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500"
      >
        No templates published yet.
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const router = useRouter();

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
  store.fetchTemplates();
});
</script>
