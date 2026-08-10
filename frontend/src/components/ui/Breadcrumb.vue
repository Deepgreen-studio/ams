<template>
  <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
    <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
      <span v-if="index > 0" class="text-slate-300">/</span>
      <RouterLink
        v-if="item.to && index < items.length - 1"
        :to="item.to"
        class="transition hover:text-slate-800"
      >
        {{ item.label }}
      </RouterLink>
      <span v-else class="font-medium text-slate-800">{{ item.label }}</span>
    </template>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const SECTION_LABELS = {
  users: 'Users',
  roles: 'Roles',
  companies: 'Companies',
  applications: 'Applications',
  content: 'Content',
  customers: 'Customers',
  support: 'Support',
  compliance: 'Compliance',
  cases: 'Cases',
  privacy: 'Privacy',
  consents: 'Consents',
  breaches: 'Breaches',
  dpia: 'DPIA',
  policies: 'Policies',
  integrations: 'Integrations',
  notifications: 'Notifications',
  automation: 'Automation',
  workflows: 'Workflows',
  scheduler: 'Scheduler',
  ai: 'AI Assistant',
  webhooks: 'Webhooks',
  analytics: 'Analytics',
  audit: 'Audit',
  monitoring: 'Monitoring',
  sync: 'Sync',
  settings: 'Settings',
  reports: 'Reports',
  releases: 'Releases',
  versions: 'Versions',
  environments: 'Environments',
  tickets: 'Tickets',
  knowledge: 'Knowledge',
  profile: 'My Profile',
};

function humanize(value) {
  return String(value || '')
    .replaceAll(/[-_]/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

function resolveParentRoute(prefix) {
  const indexName = `${prefix}.index`;
  const dashboardName = `${prefix}.dashboard`;

  if (router.hasRoute(indexName)) {
    return { name: indexName };
  }

  if (router.hasRoute(dashboardName)) {
    return { name: dashboardName };
  }

  if (router.hasRoute(prefix)) {
    return { name: prefix };
  }

  return null;
}

function labelFor(segment, prefix) {
  return SECTION_LABELS[prefix] || SECTION_LABELS[segment] || humanize(segment);
}

const items = computed(() => {
  const custom = route.meta?.breadcrumb;

  if (Array.isArray(custom) && custom.length) {
    return [{ label: 'Home', to: { name: 'dashboard' } }, ...custom];
  }

  const crumbs = [{ label: 'Home', to: { name: 'dashboard' } }];
  const routeName = typeof route.name === 'string' ? route.name : '';

  if (!routeName || routeName === 'dashboard') {
    return crumbs;
  }

  // Standalone pages like profile
  if (!routeName.includes('.')) {
    crumbs.push({ label: String(route.meta?.title || humanize(routeName)) });
    return crumbs;
  }

  const parts = routeName.split('.');
  const leaf = parts[parts.length - 1];
  const isSectionRoot = leaf === 'index' || leaf === 'dashboard';
  const parentParts = parts.slice(0, -1);

  parentParts.forEach((segment, index) => {
    const prefix = parentParts.slice(0, index + 1).join('.');
    const to = resolveParentRoute(prefix);
    const isLastParent = index === parentParts.length - 1;
    const isCurrentSection = isSectionRoot && isLastParent && to?.name === routeName;

    crumbs.push({
      label: isCurrentSection
        ? String(route.meta?.title || labelFor(segment, prefix))
        : labelFor(segment, prefix),
      to: isCurrentSection ? undefined : to || undefined,
    });
  });

  if (!isSectionRoot) {
    crumbs.push({ label: String(route.meta?.title || humanize(leaf)) });
  } else if (crumbs.length === 1) {
    crumbs.push({ label: String(route.meta?.title || humanize(routeName)) });
  }

  return crumbs;
});
</script>
