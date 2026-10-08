<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div class="overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'company_name')"
              >
                Company
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'company_name' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'company_code')"
              >
                Company Code
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'company_code' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'status')"
              >
                Status
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'status' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Org units
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'created_at')"
              >
                Created At
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'created_at' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th
              v-if="hasAnyAction"
              class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody v-if="loading">
          <tr v-for="n in 5" :key="n">
            <td colspan="12" class="px-5 py-3">
              <div class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="!companies.length">

          <tr>

            <td colspan="12" class="p-0">
              <EmptyState
                title="No companies found"
                description="No results match the current search."
                >
                <template #action>
                <slot name="empty-action" />
                </template>
              </EmptyState>
            </td>

          </tr>

        </tbody>

        <tbody v-else>
          <tr
            v-for="company in companies"
            :key="company.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            :class="can('companies.view') ? 'cursor-pointer' : ''"
            @click="openDetails(company)"
          >
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  <img
                    v-if="logoSrc(company) && !failedLogos[company.uuid]"
                    :src="logoSrc(company)"
                    :alt="company.company_name"
                    class="h-full w-full object-cover"
                    @error="failedLogos[company.uuid] = true"
                  />
                  <span v-else>{{ initials(company.company_name) }}</span>
                </div>
                <div class="min-w-0">
                  <p class="truncate font-semibold text-slate-900">{{ company.company_name }}</p>
                  <p class="truncate text-xs text-slate-500">
                    {{ company.email || company.company_code || '—' }}
                  </p>
                </div>
              </div>
            </td>
            <td class="px-5 py-4 text-slate-600">
              {{ company.company_code || '—' }}
            </td>
            <td class="px-5 py-4">
              <button
                v-if="canChangeStatus(company)"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
                :class="statusTone(company.status).text"
                :aria-expanded="statusMenuId === company.uuid"
                aria-haspopup="menu"
                aria-label="Change status"
                @click.stop="toggleStatusMenu(company.uuid, $event)"
              >
                <span class="h-1.5 w-1.5 rounded-full" :class="statusTone(company.status).dot" />
                {{ statusLabel(company.status) }}
                <ChevronDownIcon class="h-3.5 w-3.5 opacity-70" />
              </button>
              <StatusBadge v-else :status="company.status" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ company.departments_count || 0 }} dept · {{ company.teams_count || 0 }} teams ·
              {{ company.locations_count || 0 }} locs · {{ company.customers_count || 0 }} cust
            </td>
            <td class="px-5 py-4 text-slate-600">
              {{ formatDate(company.created_at) || '—' }}
            </td>
            <td v-if="hasAnyAction" class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === company.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(company.uuid, $event)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-8 py-5">
      <slot name="footer" />
    </div>

    <Teleport to="body">
      <div
        v-if="statusMenuId && statusCompany"
        class="fixed z-[80] w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="statusMenuStyle"
        @click.stop
      >
        <button
          v-for="option in statusOptions"
          :key="option.value"
          type="button"
          class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition hover:bg-zinc-50"
          :class="option.value === statusCompany.status ? 'bg-zinc-50 font-medium text-slate-900' : 'text-slate-700'"
          role="menuitem"
          @click="onStatusChange(statusCompany, option.value)"
        >
          <span class="h-1.5 w-1.5 rounded-full" :class="statusTone(option.value).dot" />
          {{ option.label }}
        </button>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeCompany"
        class="fixed z-[80] w-48 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          v-if="can('companies.view')"
          :to="{ name: 'companies.show', params: { id: activeCompany.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </RouterLink>
        <template v-if="isTrashed(activeCompany)">
          <button
            v-if="can('companies.restore')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="onRestore(activeCompany)"
          >
            <ArrowUturnLeftIcon class="h-4 w-4 text-slate-400" />
            Restore
          </button>
        </template>
        <template v-else>
          <RouterLink
            v-if="can('companies.update')"
            :to="{ name: 'companies.edit', params: { id: activeCompany.uuid } }"
            class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="closeMenu"
          >
            <PencilSquareIcon class="h-4 w-4 text-slate-400" />
            Edit
          </RouterLink>
          <button
            v-if="can('companies.delete')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
            role="menuitem"
            @click="onDelete(activeCompany)"
          >
            <TrashIcon class="h-4 w-4 text-red-500" />
            Soft Delete
          </button>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  ArrowUturnLeftIcon,
  ChevronDownIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';
import { formatDate } from '@/utils/formatters';
import { resolveMediaUrl } from '@/utils/mediaUrl';

const props = defineProps({
  companies: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  sortBy: {
    type: String,
    default: 'created_at',
  },
  sortDir: {
    type: String,
    default: 'desc',
  },
});

const emit = defineEmits(['sort', 'delete', 'restore', 'status-change']);

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'pending', label: 'Pending' },
];

const { can, canAny } = usePermissions();
const router = useRouter();

function openDetails(company) {
  if (!company?.uuid || !can('companies.view')) {
    return;
  }

  router.push({ name: 'companies.show', params: { id: company.uuid } });
}
const hasAnyAction = computed(() =>
  canAny('companies.view', 'companies.update', 'companies.delete', 'companies.restore'),
);

const failedLogos = ref({});
const openMenuId = ref(null);
const menuStyle = ref({});
const statusMenuId = ref(null);
const statusMenuStyle = ref({});

const statusCompany = computed(
  () => props.companies.find((company) => company.uuid === statusMenuId.value) || null,
);

function canChangeStatus(company) {
  return can('companies.update') && !isTrashed(company);
}

function statusLabel(status) {
  const value = status || 'active';
  return value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusTone(status) {
  switch (status) {
    case 'inactive':
      return { text: 'border-slate-300 text-slate-600', dot: 'bg-slate-400' };
    case 'suspended':
      return { text: 'border-rose-300 text-rose-700', dot: 'bg-rose-500' };
    case 'pending':
      return { text: 'border-amber-300 text-amber-700', dot: 'bg-amber-500' };
    default:
      return { text: 'border-emerald-300 text-emerald-700', dot: 'bg-emerald-500' };
  }
}

function toggleStatusMenu(id, event) {
  closeMenu();

  if (statusMenuId.value === id) {
    closeStatusMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 160;
  const menuHeight = 8 + statusOptions.length * 36;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.left), window.innerWidth - menuWidth - 8);

  statusMenuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  statusMenuId.value = id;
}

function closeStatusMenu() {
  statusMenuId.value = null;
}

function onStatusChange(company, status) {
  closeStatusMenu();
  if (!company || company.status === status) {
    return;
  }

  emit('status-change', company, status);
}

const activeCompany = computed(
  () => props.companies.find((company) => company.uuid === openMenuId.value) || null,
);

function isTrashed(company) {
  return Boolean(company?.deleted_at);
}

function logoSrc(company) {
  return resolveMediaUrl(company?.logo_url || company?.logo || '');
}

function initials(name) {
  return String(name || 'C')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function toggleMenu(id, event) {
  closeStatusMenu();

  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const company = props.companies.find((item) => item.uuid === id);
  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 192;
  const itemCount = isTrashed(company)
    ? [can('companies.view'), can('companies.restore')].filter(Boolean).length
    : [can('companies.view'), can('companies.update'), can('companies.delete')].filter(Boolean)
        .length;
  const menuHeight = 8 + Math.max(itemCount, 1) * 36;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  openMenuId.value = id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onDelete(company) {
  closeMenu();
  emit('delete', company);
}

function onRestore(company) {
  closeMenu();
  emit('restore', company);
}

function onDocumentClick() {
  closeMenu();
  closeStatusMenu();
}

function onScrollOrResize() {
  closeMenu();
  closeStatusMenu();
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  window.addEventListener('scroll', onScrollOrResize, true);
  window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  window.removeEventListener('scroll', onScrollOrResize, true);
  window.removeEventListener('resize', onScrollOrResize);
});
</script>
