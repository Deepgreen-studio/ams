<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="space-y-3 px-8 py-6">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!customers.length"
      title="No customers found"
      description="Try adjusting your search or create a new customer."
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
                @click="$emit('sort', 'email')"
              >
                Customer
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'email' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-zinc-700"
                @click="$emit('sort', 'customer_type')"
              >
                Type
                <span class="text-base leading-none text-zinc-400">
                  {{ sortBy === 'customer_type' ? (sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                </span>
              </button>
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Company
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
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 xl:table-cell">
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
            <th
              v-if="hasAnyAction"
              class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="customer in customers"
            :key="customer.uuid"
            class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
          >
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[12px] bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  {{ initials(customer.display_name) }}
                </div>
                <div class="min-w-0">
                  <p class="truncate font-semibold text-slate-900">{{ customer.display_name }}</p>
                  <p class="truncate text-xs text-slate-500">{{ customer.email || '—' }}</p>
                </div>
              </div>
            </td>
            <td class="hidden px-5 py-4 md:table-cell">
              <TypeBadge :type="customer.customer_type" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
              {{ customer.company?.company_name || '—' }}
            </td>
            <td class="px-5 py-4">
              <StatusBadge :status="customer.status" />
            </td>
            <td class="hidden px-5 py-4 text-slate-600 xl:table-cell">
              {{ customer.country || '—' }}
            </td>
            <td v-if="hasAnyAction" class="px-5 py-4">
              <div class="flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === customer.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(customer.uuid, $event)"
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
        v-if="openMenuId && activeCustomer"
        class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <RouterLink
          v-if="can('customers.view')"
          :to="{ name: 'customers.show', params: { id: activeCustomer.uuid } }"
          class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="closeMenu"
        >
          <EyeIcon class="h-4 w-4 text-slate-400" />
          View
        </RouterLink>
        <template v-if="isTrashed(activeCustomer)">
          <button
            v-if="can('customers.restore')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="onRestore(activeCustomer)"
          >
            <ArrowUturnLeftIcon class="h-4 w-4 text-slate-400" />
            Restore
          </button>
        </template>
        <template v-else>
          <RouterLink
            v-if="can('customers.update')"
            :to="{ name: 'customers.edit', params: { id: activeCustomer.uuid } }"
            class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
            role="menuitem"
            @click="closeMenu"
          >
            <PencilSquareIcon class="h-4 w-4 text-slate-400" />
            Edit
          </RouterLink>
          <button
            v-if="can('customers.delete')"
            type="button"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
            role="menuitem"
            @click="onDelete(activeCustomer)"
          >
            <TrashIcon class="h-4 w-4 text-red-500" />
            Delete
          </button>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowUturnLeftIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import StatusBadge from '@/modules/customers/components/StatusBadge.vue';
import TypeBadge from '@/modules/customers/components/TypeBadge.vue';

const props = defineProps({
  customers: {
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

const emit = defineEmits(['sort', 'delete', 'restore']);

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() =>
  canAny('customers.view', 'customers.update', 'customers.delete', 'customers.restore'),
);

const openMenuId = ref(null);
const menuStyle = ref({});

const activeCustomer = computed(
  () => props.customers.find((customer) => customer.uuid === openMenuId.value) || null,
);

function isTrashed(customer) {
  return Boolean(customer?.deleted_at);
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const customer = props.customers.find((item) => item.uuid === id);
  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const itemCount = isTrashed(customer)
    ? [can('customers.view'), can('customers.restore')].filter(Boolean).length
    : [can('customers.view'), can('customers.update'), can('customers.delete')].filter(Boolean)
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

function onDelete(customer) {
  closeMenu();
  emit('delete', customer);
}

function onRestore(customer) {
  closeMenu();
  emit('restore', customer);
}

function onDocumentClick() {
  closeMenu();
}

function onScrollOrResize() {
  closeMenu();
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
