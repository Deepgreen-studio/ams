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
            class="overflow-hidden rounded-[12px] bg-white text-left ring-1 ring-zinc-100"
            @click="openSection(kpi.key)"
          >
            <div class="px-5 pt-5">
              <p class="text-sm text-slate-500">{{ kpi.label }}</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ kpi.value }}</p>
            </div>
            <div class="mt-4 border-t border-zinc-100 px-5 py-3">
              <p class="text-xs text-slate-500">{{ kpi.detail }}</p>
            </div>
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
                  {{ app.platform_label }} · v{{ app.current_version || '-' }}
                  · {{ app.environment || 'No environment' }}
                  · {{ app.health || 'No health check' }}
                  · {{ app.last_release || 'No release' }}
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

      <section v-else-if="activeSection === 'users'" class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="stat in orgStats"
            :key="stat.label"
            class="rounded-[12px] bg-white px-5 py-4 ring-1 ring-zinc-100"
          >
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ stat.label }}</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ stat.value }}</p>
          </div>
        </div>

        <div
          v-if="!orgDepartments.length"
          class="rounded-[12px] bg-white px-6 py-10 text-center ring-1 ring-zinc-100"
        >
          <p class="text-sm font-medium text-slate-900">No departments yet</p>
          <p class="mt-1 text-sm text-slate-500">Departments, teams, and assigned users will show here.</p>
        </div>

        <article
          v-for="department in orgDepartments"
          :key="department.uuid"
          class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
        >
          <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
            <div class="flex min-w-0 items-center gap-3">
              <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] bg-brand-50 text-brand-600">
                <BuildingOffice2Icon class="h-5 w-5" />
              </span>
              <div class="min-w-0">
                <h3 class="truncate text-sm font-semibold text-slate-900">{{ department.name }}</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ department.teams?.length || 0 }} {{ (department.teams?.length || 0) === 1 ? 'team' : 'teams' }}
                  · {{ departmentUserCount(department) }} {{ departmentUserCount(department) === 1 ? 'user' : 'users' }}
                </p>
              </div>
            </div>
            <span
              v-if="department.status"
              class="rounded-full px-2.5 py-1 text-[11px] font-medium capitalize ring-1"
              :class="tone(department.status)"
            >
              {{ department.status }}
            </span>
          </header>

          <p v-if="!department.teams?.length" class="px-6 py-5 text-sm text-slate-500">No teams in this department.</p>
          <div v-else class="divide-y divide-zinc-100">
            <div v-for="team in department.teams" :key="team.uuid" class="px-6 py-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-medium text-slate-900">{{ team.name }}</p>
                  <p class="mt-0.5 text-xs text-slate-500">Manager · {{ team.manager || 'Unassigned' }}</p>
                </div>
                <span class="shrink-0 text-xs text-slate-500">
                  {{ membersForTeam(department, team).length }}
                  {{ membersForTeam(department, team).length === 1 ? 'user' : 'users' }}
                </span>
              </div>
              <ul v-if="membersForTeam(department, team).length" class="mt-3 grid gap-2 md:grid-cols-2">
                <li
                  v-for="member in membersForTeam(department, team)"
                  :key="member.uuid"
                  class="flex items-center gap-3 rounded-[10px] bg-zinc-50 px-3 py-2.5"
                >
                  <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-slate-600 ring-1 ring-zinc-200">
                    {{ initials(member.name) }}
                  </span>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-800">{{ member.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ member.email || member.location || 'No location' }}</p>
                  </div>
                </li>
              </ul>
              <p v-else class="mt-3 text-xs text-slate-400">No assigned users</p>
            </div>
          </div>

          <div v-if="membersWithoutTeam(department).length" class="border-t border-zinc-100 px-6 py-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Not on a team</p>
            <ul class="mt-3 grid gap-2 md:grid-cols-2">
              <li
                v-for="member in membersWithoutTeam(department)"
                :key="member.uuid"
                class="flex items-center gap-3 rounded-[10px] bg-zinc-50 px-3 py-2.5"
              >
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-slate-600 ring-1 ring-zinc-200">
                  {{ initials(member.name) }}
                </span>
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-slate-800">{{ member.name }}</p>
                  <p class="truncate text-xs text-slate-500">{{ member.email || member.location || 'No location' }}</p>
                </div>
              </li>
            </ul>
          </div>
        </article>

        <article
          v-if="usersOutsideDepartments.length"
          class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
        >
          <header class="border-b border-zinc-100 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Company users</h3>
            <p class="mt-0.5 text-xs text-slate-500">Assigned to this company, not placed in a department.</p>
          </header>
          <ul class="grid gap-2 p-4 md:grid-cols-2">
            <li
              v-for="member in usersOutsideDepartments"
              :key="member.uuid"
              class="flex items-center gap-3 rounded-[10px] bg-zinc-50 px-3 py-2.5"
            >
              <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-slate-600 ring-1 ring-zinc-200">
                {{ initials(member.name) }}
              </span>
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ member.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ member.email || 'No email' }}</p>
              </div>
            </li>
          </ul>
        </article>

        <article class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <header class="flex items-center gap-3 border-b border-zinc-100 px-6 py-4">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] bg-sky-50 text-sky-600">
              <MapPinIcon class="h-5 w-5" />
            </span>
            <div>
              <h3 class="text-sm font-semibold text-slate-900">Locations</h3>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ orgLocations.length }} {{ orgLocations.length === 1 ? 'location' : 'locations' }}
              </p>
            </div>
          </header>
          <p v-if="!orgLocations.length" class="px-6 py-5 text-sm text-slate-500">No locations yet.</p>
          <ul v-else class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
            <li
              v-for="item in orgLocations"
              :key="item.uuid"
              class="rounded-[12px] bg-zinc-50 px-4 py-3"
            >
              <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
                <span
                  v-if="item.status"
                  class="rounded-full px-2 py-0.5 text-[11px] font-medium capitalize ring-1"
                  :class="tone(item.status)"
                >
                  {{ item.status }}
                </span>
              </div>
              <p class="mt-1 text-xs text-slate-500">
                {{ [item.city, item.country].filter(Boolean).join(', ') || 'No address' }}
              </p>
            </li>
          </ul>
        </article>
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
import { BuildingOffice2Icon, MapPinIcon } from '@heroicons/vue/24/outline';
import { computed, defineComponent, h, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { companyService } from '@/modules/companies/services/companyService';
import { formatAppDateTime, getAppTimezone } from '@/utils/appTimezone';

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
        ['Legal name', profile.legal_name],
        ['Company code', profile.company_code],
        ['Registration number', profile.registration_number],
        ['Address', profile.address],
        ['Website', profile.website],
        ['Primary contact', profile.primary_contact],
        ['Support contact', profile.support_contact],
        ['Currency', profile.currency],
      ].filter(([, value]) => value !== undefined);
      return h('div', { class: 'rounded-[12px] bg-white p-6' }, [
        h('div', { class: 'flex items-start justify-between gap-4' }, [
          h('div', [
            h('h3', { class: 'text-base font-semibold text-slate-900' }, props.showBranding ? 'Settings' : 'Company'),
            h('p', { class: 'mt-1 text-xs text-slate-500' }, 'Identity and contact details.'),
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

const orgDepartments = computed(() => consoleData.value?.organization?.departments || []);
const orgUsers = computed(() => consoleData.value?.organization?.users || []);
const orgLocations = computed(() => consoleData.value?.organization?.locations || []);
const orgStats = computed(() => [
  { label: 'Departments', value: orgDepartments.value.length },
  {
    label: 'Teams',
    value: orgDepartments.value.reduce((count, department) => count + (department.teams?.length || 0), 0),
  },
  { label: 'Users', value: orgUsers.value.length },
  { label: 'Locations', value: orgLocations.value.length },
]);

const usersOutsideDepartments = computed(() => {
  const names = new Set(orgDepartments.value.map((department) => department.name));
  return orgUsers.value.filter((user) => !user.department || !names.has(user.department));
});

function departmentUserCount(department) {
  return orgUsers.value.filter((user) => user.department === department.name).length;
}

function membersForTeam(department, team) {
  return orgUsers.value.filter((user) => user.department === department.name && user.team === team.name);
}

function membersWithoutTeam(department) {
  return orgUsers.value.filter((user) => user.department === department.name && !user.team);
}

function initials(name) {
  return String(name || '?')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase();
}

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
  return formatAppDateTime(value, getAppTimezone());
}

function tone(status) {
  if (['active', 'healthy', 'deployed', 'production'].includes(status)) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
  if (['inactive', 'unhealthy', 'failed', 'suspended'].includes(status)) return 'bg-rose-50 text-rose-700 ring-rose-200';
  return 'bg-amber-50 text-amber-700 ring-amber-200';
}
</script>
