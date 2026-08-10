<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!companies.length"
      title="No companies found"
      description="Try adjusting your search or create a new company."
      class="px-8 py-6"
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="overflow-x-auto px-3">
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
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'country')"
              >
                Country
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'country' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
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
            <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="company in companies"
            :key="company.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  <img
                    v-if="company.logo_url"
                    :src="company.logo_url"
                    :alt="company.company_name"
                    class="h-full w-full object-cover"
                  />
                  <span v-else>{{ initials(company.company_name) }}</span>
                </div>
                <div class="min-w-0">
                  <p class="truncate font-semibold text-slate-900">{{ company.company_name }}</p>
                  <p class="truncate text-xs text-slate-500">
                    {{ company.email || company.registration_number || '—' }}
                  </p>
                </div>
              </div>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              {{ company.country || '—' }}
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="company.status" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ company.departments_count || 0 }} dept · {{ company.teams_count || 0 }} teams ·
              {{ company.locations_count || 0 }} locs
            </td>
            <td class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === company.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(company.uuid)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>

                <div
                  v-if="openMenuId === company.uuid"
                  class="absolute right-0 top-10 z-20 w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
                  role="menu"
                >
                  <RouterLink
                    :to="{ name: 'companies.show', params: { id: company.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <EyeIcon class="h-4 w-4 text-slate-400" />
                    View
                  </RouterLink>
                  <RouterLink
                    :to="{ name: 'companies.edit', params: { id: company.uuid } }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="closeMenu"
                  >
                    <PencilSquareIcon class="h-4 w-4 text-slate-400" />
                    Edit
                  </RouterLink>
                  <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                    role="menuitem"
                    @click="onDelete(company)"
                  >
                    <TrashIcon class="h-4 w-4 text-red-500" />
                    Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-8 py-5">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { EllipsisVerticalIcon, EyeIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

defineProps({
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

const emit = defineEmits(['sort', 'delete']);

const openMenuId = ref(null);

function initials(name) {
  return String(name || 'C')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onDelete(company) {
  closeMenu();
  emit('delete', company);
}

function onDocumentClick() {
  closeMenu();
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});
</script>
