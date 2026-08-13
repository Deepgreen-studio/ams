<template>
  <div class="mb-6">
    <div class="border-b border-zinc-200">
      <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Analytics sections">
        <RouterLink
          v-for="group in groups"
          :key="group.id"
          :to="group.to"
          class="shrink-0 border-b-2 px-3.5 py-2.5 text-sm font-medium transition-colors"
          :class="
            isGroupActive(group.id)
              ? 'border-brand-600 text-brand-700'
              : 'border-transparent text-slate-500 hover:border-zinc-300 hover:text-slate-800'
          "
        >
          {{ group.label }}
        </RouterLink>
      </nav>
    </div>

    <nav
      v-if="activeLinks.length"
      class="mt-3 flex flex-wrap gap-1.5"
      aria-label="Analytics subsection"
    >
      <RouterLink
        v-for="item in activeLinks"
        :key="item.name"
        :to="item.to"
        class="rounded-[10px] px-3 py-1.5 text-xs font-medium transition"
        :class="
          isLinkActive(item)
            ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100'
            : 'bg-white text-slate-600 ring-1 ring-zinc-200 hover:bg-zinc-50'
        "
      >
        {{ item.label }}
      </RouterLink>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const groups = [
  {
    id: 'overview',
    label: 'Overview',
    to: { name: 'analytics.dashboard' },
    prefixes: [],
    exact: ['analytics.dashboard'],
    links: [],
  },
  {
    id: 'dashboards',
    label: 'Dashboards',
    to: { name: 'analytics.dashboards' },
    prefixes: ['analytics.dashboards'],
    links: [],
  },
  {
    id: 'templates',
    label: 'Templates',
    to: { name: 'analytics.templates' },
    prefixes: ['analytics.templates'],
    links: [],
  },
  {
    id: 'reports',
    label: 'Reports',
    to: { name: 'analytics.reports' },
    prefixes: ['analytics.reports'],
    links: [],
  },
  {
    id: 'saved-reports',
    label: 'Saved Reports',
    to: { name: 'analytics.saved-reports' },
    prefixes: ['analytics.saved-reports'],
    links: [],
  },
  {
    id: 'saved-views',
    label: 'Saved Views',
    to: { name: 'analytics.saved-views' },
    prefixes: ['analytics.saved-views'],
    links: [],
  },
  {
    id: 'events',
    label: 'Events',
    to: { name: 'analytics.events' },
    prefixes: ['analytics.events'],
    links: [],
  },
  {
    id: 'business',
    label: 'Business',
    to: { name: 'analytics.business' },
    prefixes: ['analytics.business'],
    links: [
      { name: 'analytics.business', label: 'Overview', to: { name: 'analytics.business' } },
      { name: 'analytics.business.customers', label: 'Customers', to: { name: 'analytics.business.customers' } },
      { name: 'analytics.business.revenue', label: 'Revenue', to: { name: 'analytics.business.revenue' } },
      { name: 'analytics.business.applications', label: 'Applications', to: { name: 'analytics.business.applications' } },
      { name: 'analytics.business.growth', label: 'Growth', to: { name: 'analytics.business.growth' } },
    ],
  },
  {
    id: 'executive',
    label: 'Executive',
    to: { name: 'analytics.executive' },
    prefixes: ['analytics.executive'],
    links: [
      { name: 'analytics.executive', label: 'CEO', to: { name: 'analytics.executive' } },
      { name: 'analytics.executive.admin', label: 'Admin', to: { name: 'analytics.executive.admin' } },
      { name: 'analytics.executive.operations', label: 'Operations', to: { name: 'analytics.executive.operations' } },
      { name: 'analytics.executive.compliance', label: 'Compliance', to: { name: 'analytics.executive.compliance' } },
      { name: 'analytics.executive.support', label: 'Support', to: { name: 'analytics.executive.support' } },
      { name: 'analytics.executive.customer', label: 'Customer', to: { name: 'analytics.executive.customer' } },
      { name: 'analytics.executive.scorecards', label: 'Scorecards', to: { name: 'analytics.executive.scorecards' } },
      { name: 'analytics.executive.trends', label: 'Trends', to: { name: 'analytics.executive.trends' } },
      { name: 'analytics.executive.forecast', label: 'Forecast', to: { name: 'analytics.executive.forecast' } },
    ],
  },
  {
    id: 'security',
    label: 'Security',
    to: { name: 'analytics.security' },
    prefixes: ['analytics.security'],
    links: [
      { name: 'analytics.security', label: 'Overview', to: { name: 'analytics.security' } },
      { name: 'analytics.security.audit', label: 'Audit', to: { name: 'analytics.security.audit' } },
      { name: 'analytics.security.timeline', label: 'Timeline', to: { name: 'analytics.security.timeline' } },
      { name: 'analytics.security.risk', label: 'Risk', to: { name: 'analytics.security.risk' } },
      { name: 'analytics.security.heatmap', label: 'Heatmap', to: { name: 'analytics.security.heatmap' } },
      { name: 'analytics.security.export', label: 'Export', to: { name: 'analytics.security.export' } },
    ],
  },
  {
    id: 'operational',
    label: 'Operational',
    to: { name: 'analytics.operational' },
    prefixes: ['analytics.operational', 'analytics.delivery', 'analytics.automation', 'analytics.workflows', 'analytics.ai'],
    links: [
      { name: 'analytics.operational', label: 'Overview', to: { name: 'analytics.operational' } },
      { name: 'analytics.delivery', label: 'Delivery', to: { name: 'analytics.delivery' } },
      { name: 'analytics.automation', label: 'Automation', to: { name: 'analytics.automation' } },
      { name: 'analytics.workflows', label: 'Workflows', to: { name: 'analytics.workflows' } },
      { name: 'analytics.ai', label: 'AI usage', to: { name: 'analytics.ai' } },
    ],
  },
];

const routeName = computed(() => String(route.name || ''));

const activeGroup = computed(
  () => groups.find((group) => isGroupActive(group.id)) || groups[0],
);

const activeLinks = computed(() => activeGroup.value?.links ?? []);

function matchesPrefix(name, prefixes) {
  return prefixes.some((prefix) => name === prefix || name.startsWith(`${prefix}.`) || name.startsWith(prefix));
}

function isGroupActive(groupId) {
  const group = groups.find((item) => item.id === groupId);
  if (!group) {
    return false;
  }

  if (group.exact?.includes(routeName.value)) {
    return true;
  }

  if (!group.prefixes.length) {
    return false;
  }

  return matchesPrefix(routeName.value, group.prefixes);
}

function isLinkActive(item) {
  return routeName.value === item.name;
}
</script>
