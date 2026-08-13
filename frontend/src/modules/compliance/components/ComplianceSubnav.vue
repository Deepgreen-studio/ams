<template>
  <div class="mb-6">
    <div class="border-b border-zinc-200">
      <nav class="-mb-px flex gap-x-0.5 overflow-x-auto" aria-label="Compliance sections">
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
      aria-label="Compliance subsection"
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
    id: 'cases',
    label: 'Cases',
    to: { name: 'compliance.dashboard' },
    prefixes: ['compliance.dashboard', 'compliance.cases.'],
    links: [
      { name: 'compliance.dashboard', label: 'Dashboard', to: { name: 'compliance.dashboard' } },
      { name: 'compliance.cases.index', label: 'All cases', to: { name: 'compliance.cases.index' } },
    ],
  },
  {
    id: 'privacy',
    label: 'Privacy',
    to: { name: 'compliance.privacy.dashboard' },
    prefixes: ['compliance.privacy.'],
    links: [
      { name: 'compliance.privacy.dashboard', label: 'Dashboard', to: { name: 'compliance.privacy.dashboard' } },
      { name: 'compliance.privacy.index', label: 'Requests', to: { name: 'compliance.privacy.index' } },
    ],
  },
  {
    id: 'consents',
    label: 'Consent',
    to: { name: 'compliance.consents.dashboard' },
    prefixes: ['compliance.consents.'],
    links: [
      { name: 'compliance.consents.dashboard', label: 'Dashboard', to: { name: 'compliance.consents.dashboard' } },
      { name: 'compliance.consents.index', label: 'Consents', to: { name: 'compliance.consents.index' } },
      { name: 'compliance.consents.preferences', label: 'Preferences', to: { name: 'compliance.consents.preferences' } },
    ],
  },
  {
    id: 'breaches',
    label: 'Breaches',
    to: { name: 'compliance.breaches.dashboard' },
    prefixes: ['compliance.breaches.'],
    links: [
      { name: 'compliance.breaches.dashboard', label: 'Dashboard', to: { name: 'compliance.breaches.dashboard' } },
      { name: 'compliance.breaches.index', label: 'Incidents', to: { name: 'compliance.breaches.index' } },
      { name: 'compliance.breaches.notifications', label: 'Notifications', to: { name: 'compliance.breaches.notifications' } },
    ],
  },
  {
    id: 'dpia',
    label: 'DPIA',
    to: { name: 'compliance.dpia.dashboard' },
    prefixes: ['compliance.dpia.'],
    links: [
      { name: 'compliance.dpia.dashboard', label: 'Dashboard', to: { name: 'compliance.dpia.dashboard' } },
      { name: 'compliance.dpia.history', label: 'History', to: { name: 'compliance.dpia.history' } },
      { name: 'compliance.dpia.wizard', label: 'Wizard', to: { name: 'compliance.dpia.wizard' } },
      { name: 'compliance.dpia.risk', label: 'Risk matrix', to: { name: 'compliance.dpia.risk' } },
      { name: 'compliance.dpia.mitigation', label: 'Mitigation', to: { name: 'compliance.dpia.mitigation' } },
    ],
  },
  {
    id: 'policies',
    label: 'Policies',
    to: { name: 'compliance.policies.dashboard' },
    prefixes: ['compliance.policies.'],
    links: [
      { name: 'compliance.policies.dashboard', label: 'Dashboard', to: { name: 'compliance.policies.dashboard' } },
      { name: 'compliance.policies.index', label: 'Policies', to: { name: 'compliance.policies.index' } },
      { name: 'compliance.policies.approvals', label: 'Approvals', to: { name: 'compliance.policies.approvals' } },
    ],
  },
  {
    id: 'analytics',
    label: 'Analytics',
    to: { name: 'compliance.analytics.dashboard' },
    prefixes: ['compliance.analytics.'],
    links: [
      { name: 'compliance.analytics.dashboard', label: 'Overview', to: { name: 'compliance.analytics.dashboard' } },
      { name: 'compliance.analytics.risks', label: 'Risk charts', to: { name: 'compliance.analytics.risks' } },
      { name: 'compliance.analytics.gdpr', label: 'GDPR reports', to: { name: 'compliance.analytics.gdpr' } },
      { name: 'compliance.analytics.consent', label: 'Consent reports', to: { name: 'compliance.analytics.consent' } },
      { name: 'compliance.analytics.audit', label: 'Audit reports', to: { name: 'compliance.analytics.audit' } },
    ],
  },
];

const routeName = computed(() => String(route.name || ''));

const activeGroup = computed(
  () => groups.find((group) => isGroupActive(group.id)) || groups[0],
);

const activeLinks = computed(() => activeGroup.value?.links ?? []);

function matchesPrefix(name, prefixes) {
  return prefixes.some((prefix) => name === prefix || name.startsWith(prefix));
}

function isGroupActive(groupId) {
  const group = groups.find((item) => item.id === groupId);
  if (!group) {
    return false;
  }

  return matchesPrefix(routeName.value, group.prefixes);
}

function isLinkActive(item) {
  const name = routeName.value;

  if (item.name === 'compliance.cases.index') {
    return (
      name === 'compliance.cases.index' ||
      name === 'compliance.cases.show' ||
      name === 'compliance.cases.edit' ||
      name === 'compliance.cases.create'
    );
  }

  if (item.name === 'compliance.privacy.index') {
    return (
      name === 'compliance.privacy.index' ||
      name === 'compliance.privacy.show' ||
      name === 'compliance.privacy.verify' ||
      name === 'compliance.privacy.create'
    );
  }

  if (item.name === 'compliance.consents.index') {
    return name === 'compliance.consents.index' || name === 'compliance.consents.show' || name === 'compliance.consents.create';
  }

  if (item.name === 'compliance.breaches.index') {
    return (
      name === 'compliance.breaches.index' ||
      name === 'compliance.breaches.show' ||
      name === 'compliance.breaches.create' ||
      name === 'compliance.breaches.affected'
    );
  }

  if (item.name === 'compliance.dpia.wizard') {
    return name === 'compliance.dpia.wizard' || name === 'compliance.dpia.wizard.edit';
  }

  if (item.name === 'compliance.dpia.history') {
    return name === 'compliance.dpia.history' || name === 'compliance.dpia.show';
  }

  if (item.name === 'compliance.policies.index') {
    return (
      name === 'compliance.policies.index' ||
      name === 'compliance.policies.show' ||
      name === 'compliance.policies.create' ||
      name === 'compliance.policies.versions' ||
      name === 'compliance.policies.compare'
    );
  }

  return name === item.name;
}
</script>
