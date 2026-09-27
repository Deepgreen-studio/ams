<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'companies.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to company
      </RouterLink>
    </Teleport>

    <div v-if="error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="item in 6" :key="item" class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else-if="consoleData" class="space-y-6">
      <div class="flex gap-2 overflow-x-auto">
        <button
          v-for="section in consoleData.sections"
          :key="section.key"
          type="button"
          class="rounded-full px-4 py-2 text-sm font-medium"
          :class="activeSection === section.key ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-zinc-200'"
          @click="activeSection = section.key"
        >
          {{ section.label }}
        </button>
      </div>

      <section v-if="activeSection === 'overview'" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <button
            v-for="kpi in consoleData.kpis"
            :key="kpi.key"
            type="button"
            class="rounded-[12px] bg-white p-5 text-left ring-1 ring-zinc-100"
            @click="openSection(kpi.key)"
          >
            <p class="text-sm text-slate-500">{{ kpi.label }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ kpi.value }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ kpi.detail }}</p>
          </button>
        </div>
        <ProfileCard :profile="consoleData.profile" />
      </section>

      <section v-else-if="activeSection === 'applications'" class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-[12px] bg-white p-6 xl:col-span-2">
          <h3 class="text-base font-semibold text-slate-900">Applications</h3>
          <p v-if="!consoleData.applications?.length" class="mt-4 text-sm text-slate-500">No applications for this company.</p>
          <ul v-else class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <li v-for="app in consoleData.applications" :key="app.uuid">
              <RouterLink
                :to="{ name: 'applications.show', params: { id: app.uuid } }"
                class="block px-3.5 py-3 transition hover:bg-white"
              >
                <div class="flex items-center justify-between gap-3">
                  <p class="text-sm font-medium text-slate-900">{{ app.name }}</p>
                  <span class="rounded-full px-2 py-0.5 text-[11px] font-medium ring-1" :class="tone(app.status)">{{ app.status_label }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                  {{ app.platform_label }} · v{{ app.current_version || '-' }} · {{ app.operational_status }}
                </p>
              </RouterLink>
            </li>
          </ul>
        </div>
        <div class="space-y-6">
          <div class="rounded-[12px] bg-white p-6">
            <h3 class="text-base font-semibold text-slate-900">Platform status</h3>
            <ul class="mt-4 space-y-2">
              <li v-for="platform in consoleData.platforms" :key="platform.platform" class="flex justify-between rounded-[12px] bg-slate-50/60 px-3.5 py-3 text-sm">
                <span>{{ platform.label }}</span>
                <span class="text-slate-500">{{ platform.active }} / {{ platform.total }}</span>
              </li>
            </ul>
          </div>
          <ListCard title="Environment status" :items="consoleData.environments" empty="No environments yet.">
            <template #item="{ item }">
              <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ item.application_name }} · {{ item.type_label }} · {{ item.health_label }}</p>
            </template>
          </ListCard>
        </div>
        <ListCard title="Versions" :items="consoleData.versions" empty="No versions yet.">
          <template #item="{ item }">
            <p class="text-sm font-medium text-slate-900">{{ item.version_number }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ item.application_name }} · {{ item.status_label }}</p>
          </template>
        </ListCard>
        <ListCard title="Releases" :items="consoleData.releases" empty="No releases yet.">
          <template #item="{ item }">
            <RouterLink v-if="item.application_uuid" :to="{ name: 'applications.releases.show', params: { id: item.application_uuid, releaseId: item.uuid } }">
              <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ item.application_name }} · {{ item.status_label }}</p>
            </RouterLink>
          </template>
        </ListCard>
      </section>

      <section v-else-if="activeSection === 'users'" class="space-y-6">
        <div v-for="department in consoleData.organization?.departments || []" :key="department.uuid" class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">{{ department.name }}</h3>
          <div v-for="team in department.teams" :key="team.uuid" class="mt-4 rounded-[12px] bg-slate-50/70 p-4">
            <p class="text-sm font-medium text-slate-800">{{ team.name }}</p>
            <p class="text-xs text-slate-500">Manager: {{ team.manager || 'Unassigned' }}</p>
            <p v-if="!team.members.length" class="mt-2 text-xs text-slate-500">No assigned users.</p>
            <ul v-else class="mt-2 space-y-1">
              <li v-for="member in team.members" :key="member.uuid" class="text-sm text-slate-700">
                {{ member.name }}<span v-if="member.location"> · {{ member.location }}</span>
              </li>
            </ul>
          </div>
        </div>
        <ListCard title="Locations" :items="consoleData.organization?.locations || []" empty="No locations yet.">
          <template #item="{ item }">
            <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ [item.city, item.country].filter(Boolean).join(', ') || item.status }}</p>
          </template>
        </ListCard>
      </section>

      <section v-else-if="activeSection === 'integrations'">
        <ListCard title="Integrations" :items="consoleData.integrations" empty="No integrations yet.">
          <template #item="{ item }">
            <RouterLink :to="{ name: 'integrations.show', params: { id: item.uuid } }">
              <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ item.type_label }} · {{ item.status_label }} · {{ item.health_label }}</p>
            </RouterLink>
          </template>
        </ListCard>
      </section>

      <section v-else-if="activeSection === 'support'">
        <ListCard title="Support issues" :items="consoleData.support_issues" empty="No open issues.">
          <template #item="{ item }">
            <RouterLink :to="{ name: 'support.tickets.show', params: { id: item.uuid } }">
              <p class="text-sm font-medium text-slate-900">{{ item.subject }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ item.ticket_number }} · {{ item.priority_label }} · {{ item.status_label }}</p>
            </RouterLink>
          </template>
        </ListCard>
      </section>

      <section v-else-if="activeSection === 'compliance'" class="rounded-[12px] bg-white p-6">
        <h3 class="text-base font-semibold text-slate-900">Compliance / GDPR</h3>
        <p class="mt-2 text-sm text-slate-600">
          {{ complianceCount }} open privacy requests and data breach cases for this company.
        </p>
      </section>

      <section v-else-if="activeSection === 'settings'">
        <ProfileCard :profile="consoleData.profile" show-branding />
      </section>

      <section v-else-if="activeSection === 'activity'" class="rounded-[12px] bg-white p-6">
        <h3 class="text-base font-semibold text-slate-900">Activity</h3>
        <p v-if="!consoleData.activity?.length" class="mt-4 text-sm text-slate-500">No activity yet.</p>
        <ul v-else class="mt-4 max-h-[28rem] divide-y divide-slate-100 overflow-y-auto rounded-[12px] bg-slate-50/60">
          <li v-for="entry in consoleData.activity" :key="entry.id" class="grid gap-1 px-3.5 py-3 sm:grid-cols-[1fr_auto]">
            <div>
              <p class="text-sm font-medium text-slate-900">{{ entry.action }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ entry.user }} · {{ entry.entity }}</p>
            </div>
            <p class="text-xs text-slate-500">{{ formatInZone(entry.created_at) }}</p>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { companyService } from '@/modules/companies/services/companyService';

const ListCard = defineComponent({
  props: {
    title: { type: String, required: true },
    items: { type: Array, default: () => [] },
    empty: { type: String, default: 'Nothing to show.' },
  },
  setup(props, { slots }) {
    return () => h('div', { class: 'rounded-[12px] bg-white p-6' }, [
      h('h3', { class: 'text-base font-semibold text-slate-900' }, props.title),
      props.items.length
        ? h('ul', { class: 'mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-[12px] bg-slate-50/60' },
          props.items.map((item) => h('li', { key: item.uuid || item.id, class: 'px-3.5 py-3' }, slots.item?.({ item }))))
        : h('p', { class: 'mt-4 text-sm text-slate-500' }, props.empty),
    ]);
  },
});

const ProfileCard = defineComponent({
  props: {
    profile: { type: Object, default: null },
    showBranding: { type: Boolean, default: false },
  },
  setup(props) {
    return () => {
      const profile = props.profile || {};
      const rows = [
        ['Display name', profile.display_name],
        ['Company code', profile.company_code],
        ['Address', profile.address],
        ['Website', profile.website],
        ['Primary contact', profile.primary_contact],
        ['Support contact', profile.support_contact],
        ['Timezone', profile.timezone],
        ['Currency', profile.currency],
      ];
      return h('div', { class: 'rounded-[12px] bg-white p-6' }, [
        h('div', { class: 'flex items-start justify-between gap-4' }, [
          h('div', [
            h('h3', { class: 'text-base font-semibold text-slate-900' }, props.showBranding ? 'Settings' : 'Company'),
            h('p', { class: 'mt-1 text-xs text-slate-500' }, 'Currency is an explicit company setting and is not inferred from the country.'),
          ]),
          profile.logo_url ? h('img', { src: profile.logo_url, alt: '', class: 'h-12 w-12 rounded-[10px] object-cover' }) : null,
        ]),
        h('dl', { class: 'mt-4 divide-y divide-slate-100' }, rows.map(([label, value]) => h('div', { class: 'grid grid-cols-[9rem_1fr] gap-3 py-2.5', key: label }, [
          h('dt', { class: 'text-xs text-slate-500' }, label),
          h('dd', { class: 'text-sm font-medium text-slate-900' }, value || '-'),
        ]))),
        props.showBranding ? h('p', { class: 'mt-4 text-xs text-slate-500' }, `Branding ${profile.primary_color || '-'} / ${profile.secondary_color || '-'}`) : null,
      ]);
    };
  },
});

const route = useRoute();
const loading = ref(true);
const error = ref('');
const consoleData = ref(null);
const activeSection = ref('overview');

const complianceCount = computed(() => consoleData.value?.kpis?.find((kpi) => kpi.key === 'compliance')?.value ?? 0);

onMounted(load);

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await companyService.console(route.params.id);
    consoleData.value = data.data?.console ?? null;
    activeSection.value = consoleData.value?.sections?.[0]?.key || 'overview';
  } catch (err) {
    error.value = err?.message || 'Unable to load the company console';
  } finally {
    loading.value = false;
  }
}

function openSection(key) {
  const map = { applications: 'applications', support: 'support', releases: 'applications', integrations: 'integrations', users: 'users', compliance: 'compliance' };
  const next = map[key];
  if (next && consoleData.value?.sections?.some((section) => section.key === next)) {
    activeSection.value = next;
  }
}

function formatInZone(value) {
  if (!value) return '-';
  const timeZone = consoleData.value?.timezone || 'Asia/Kolkata';
  return new Intl.DateTimeFormat('en-IN', { timeZone, dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function tone(status) {
  if (['active', 'healthy', 'deployed', 'production'].includes(status)) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
  if (['inactive', 'unhealthy', 'failed', 'suspended'].includes(status)) return 'bg-rose-50 text-rose-700 ring-rose-200';
  return 'bg-amber-50 text-amber-700 ring-amber-200';
}
</script>
