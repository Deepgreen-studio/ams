<template>
  <div class="mb-4 flex flex-wrap gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="item.to"
      class="rounded-lg px-3 py-2 text-sm font-medium transition"
      :class="
        isActive(item.name)
          ? 'bg-brand-50 text-brand-700'
          : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'
      "
    >
      {{ item.label }}
    </RouterLink>
  </div>
</template>

<script setup>
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = [
  { name: 'compliance.dashboard', label: 'Cases Dashboard', to: { name: 'compliance.dashboard' } },
  { name: 'compliance.cases.index', label: 'Cases', to: { name: 'compliance.cases.index' } },
  { name: 'compliance.privacy.dashboard', label: 'Privacy Dashboard', to: { name: 'compliance.privacy.dashboard' } },
  { name: 'compliance.privacy.index', label: 'Privacy Requests', to: { name: 'compliance.privacy.index' } },
  { name: 'compliance.consents.dashboard', label: 'Consent Dashboard', to: { name: 'compliance.consents.dashboard' } },
  { name: 'compliance.consents.index', label: 'Consents', to: { name: 'compliance.consents.index' } },
  { name: 'compliance.breaches.dashboard', label: 'Breach Dashboard', to: { name: 'compliance.breaches.dashboard' } },
  { name: 'compliance.breaches.index', label: 'Breaches', to: { name: 'compliance.breaches.index' } },
  { name: 'compliance.dpia.dashboard', label: 'DPIA Dashboard', to: { name: 'compliance.dpia.dashboard' } },
  { name: 'compliance.dpia.history', label: 'DPIA History', to: { name: 'compliance.dpia.history' } },
  { name: 'compliance.dpia.wizard', label: 'DPIA Wizard', to: { name: 'compliance.dpia.wizard' } },
  { name: 'compliance.dpia.risk', label: 'Risk Matrix', to: { name: 'compliance.dpia.risk' } },
  { name: 'compliance.dpia.mitigation', label: 'Mitigation', to: { name: 'compliance.dpia.mitigation' } },
  { name: 'compliance.policies.dashboard', label: 'Policy Dashboard', to: { name: 'compliance.policies.dashboard' } },
  { name: 'compliance.policies.index', label: 'Policies', to: { name: 'compliance.policies.index' } },
  { name: 'compliance.policies.approvals', label: 'Policy Approvals', to: { name: 'compliance.policies.approvals' } },
  { name: 'compliance.analytics.dashboard', label: 'Analytics', to: { name: 'compliance.analytics.dashboard' } },
  { name: 'compliance.analytics.risks', label: 'Risk Charts', to: { name: 'compliance.analytics.risks' } },
  { name: 'compliance.analytics.gdpr', label: 'GDPR Reports', to: { name: 'compliance.analytics.gdpr' } },
  { name: 'compliance.analytics.consent', label: 'Consent Reports', to: { name: 'compliance.analytics.consent' } },
  { name: 'compliance.analytics.audit', label: 'Audit Reports', to: { name: 'compliance.analytics.audit' } },
];

function isActive(name) {
  if (name === 'compliance.cases.index') {
    return (
      route.name === 'compliance.cases.index' ||
      route.name === 'compliance.cases.show' ||
      route.name === 'compliance.cases.edit'
    );
  }
  if (name === 'compliance.privacy.index') {
    return (
      route.name === 'compliance.privacy.index' ||
      route.name === 'compliance.privacy.show' ||
      route.name === 'compliance.privacy.verify'
    );
  }
  if (name === 'compliance.consents.index') {
    return (
      route.name === 'compliance.consents.index' ||
      route.name === 'compliance.consents.show' ||
      route.name === 'compliance.consents.create'
    );
  }
  if (name === 'compliance.breaches.index') {
    return (
      route.name === 'compliance.breaches.index' ||
      route.name === 'compliance.breaches.show' ||
      route.name === 'compliance.breaches.create' ||
      route.name === 'compliance.breaches.affected'
    );
  }
  if (name === 'compliance.dpia.wizard') {
    return route.name === 'compliance.dpia.wizard' || route.name === 'compliance.dpia.wizard.edit';
  }
  if (name === 'compliance.dpia.history') {
    return route.name === 'compliance.dpia.history' || route.name === 'compliance.dpia.show';
  }
  if (name === 'compliance.policies.index') {
    return (
      route.name === 'compliance.policies.index' ||
      route.name === 'compliance.policies.show' ||
      route.name === 'compliance.policies.create' ||
      route.name === 'compliance.policies.versions' ||
      route.name === 'compliance.policies.compare'
    );
  }
  if (name === 'compliance.analytics.dashboard') {
    return route.name === 'compliance.analytics.dashboard';
  }
  return route.name === name;
}
</script>
