<template>
  <div>
    <!-- <PageHeader
      title="Assign roles to user"
      description="Sync one or more roles onto a platform user."
    /> -->

    <div
      v-if="rolesStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ rolesStore.successMessage }}
    </div>
    <div
      v-if="error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
      <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">User</label>
        <div ref="comboboxRoot" class="relative max-w-xl">
          <input
            v-model="userSearch"
            type="text"
            role="combobox"
            :aria-expanded="dropdownOpen"
            aria-controls="user-combobox-list"
            aria-autocomplete="list"
            placeholder="Search and select a user..."
            class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 pr-11 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-0"
            :disabled="loadingUsers"
            autocomplete="off"
            @focus="openDropdown"
            @input="openDropdown"
            @keydown.escape="closeDropdown"
            @keydown.enter.prevent="selectHighlighted"
            @keydown.down.prevent="moveHighlight(1)"
            @keydown.up.prevent="moveHighlight(-1)"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400 transition hover:text-slate-600"
            tabindex="-1"
            :aria-label="dropdownOpen ? 'Close user list' : 'Open user list'"
            @click="toggleDropdown"
          >
            <ChevronDownIcon
              class="h-5 w-5 transition"
              :class="dropdownOpen ? 'rotate-180' : ''"
            />
          </button>

          <ul
            v-if="dropdownOpen"
            id="user-combobox-list"
            role="listbox"
            class="absolute z-20 mt-1.5 max-h-56 w-full overflow-y-auto rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
          >
            <li v-if="loadingUsers" class="px-3.5 py-2.5 text-sm text-slate-500">
              Loading users...
            </li>
            <li v-else-if="!filteredUsers.length" class="px-3.5 py-2.5 text-sm text-slate-500">
              No users found.
            </li>
            <li
              v-for="(user, index) in filteredUsers"
              v-else
              :key="user.uuid"
              role="option"
              :aria-selected="user.uuid === userIdentifier"
              class="cursor-pointer px-3.5 py-2.5 text-sm"
              :class="optionClass(user, index)"
              @mousedown.prevent="selectUser(user)"
              @mouseenter="highlightedIndex = index"
            >
              <span class="font-medium text-slate-800">{{ userLabel(user) }}</span>
              <span class="mt-0.5 block text-xs text-slate-500">{{ user.email }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-6">
        <label class="mb-3 block text-sm font-medium text-slate-700">Roles</label>
        <div
          class="grid gap-5 rounded-[12px] border border-zinc-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
        >
          <div v-for="section in roleSections" :key="section.key" class="min-w-0 space-y-1">
            <label
              v-for="role in section.roles"
              :key="role.uuid"
              class="flex cursor-pointer items-center gap-3 rounded-[10px] px-2.5 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
            >
              <input
                v-model="selectedRole"
                type="radio"
                name="assign-role"
                :value="role.uuid"
                class="h-4 w-4 shrink-0 border-zinc-300 accent-brand-600 focus:ring-brand-500"
              />
              <span class="font-medium text-slate-800">{{ role.display_name }}</span>
            </label>
          </div>

          <p
            v-if="!roleSections.length"
            class="px-1 py-2 text-sm text-slate-500 sm:col-span-2 lg:col-span-3 xl:col-span-5"
          >
            No roles available.
          </p>
        </div>
      </div>

      <div class="mt-6 flex justify-end">
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="rolesStore.saving || !userIdentifier || !selectedRole"
          @click="onAssign"
        >
          {{ rolesStore.saving ? 'Saving...' : 'Sync user roles' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { useRolesStore } from '@/modules/roles/stores/roles';
import { userService } from '@/modules/users/services/userService';

const rolesStore = useRolesStore();
const users = ref([]);
const userIdentifier = ref('');
const userSearch = ref('');
const selectedRole = ref('');
const error = ref('');
const loadingUsers = ref(false);
const dropdownOpen = ref(false);
const highlightedIndex = ref(0);
const comboboxRoot = ref(null);
const syncingSearchFromSelection = ref(false);

const ROLE_SECTION_DEFINITIONS = [
  {
    key: 'administration',
    label: 'Administration',
    names: ['super-admin', 'company-admin', 'admin', 'manager'],
  },
  {
    key: 'engineering',
    label: 'Engineering',
    names: ['developer', 'qa-tester'],
  },
  {
    key: 'support',
    label: 'Support',
    names: ['support-manager', 'support-agent'],
  },
  {
    key: 'content',
    label: 'Content',
    names: ['content-manager', 'content-writer', 'content-editor'],
  },
  {
    key: 'access',
    label: 'Access',
    names: ['compliance-officer', 'customer', 'read-only-user'],
  },
];

const roleSections = computed(() => {
  const roles = [...(rolesStore.roles || [])];
  const used = new Set();

  const sections = ROLE_SECTION_DEFINITIONS.map((section) => {
    const matched = section.names
      .map((name) => roles.find((role) => role.name === name))
      .filter(Boolean);

    matched.forEach((role) => used.add(role.uuid));

    return {
      key: section.key,
      label: section.label,
      roles: matched,
    };
  }).filter((section) => section.roles.length);

  const otherRoles = roles
    .filter((role) => !used.has(role.uuid))
    .sort((a, b) =>
      String(a.display_name || a.name).localeCompare(String(b.display_name || b.name))
    );

  if (otherRoles.length) {
    sections.push({
      key: 'other',
      label: 'Other',
      roles: otherRoles,
    });
  }

  return sections;
});

const filteredUsers = computed(() => {
  const query = userSearch.value.trim().toLowerCase();
  const selected = users.value.find((user) => user.uuid === userIdentifier.value);

  // While a selected label is shown, keep showing the full list until the user types.
  if (!query || (selected && userSearch.value === formatUser(selected))) {
    return users.value;
  }

  return users.value.filter((user) => {
    const name = (user.full_name || user.name || '').toLowerCase();
    const email = (user.email || '').toLowerCase();
    return name.includes(query) || email.includes(query);
  });
});

watch(filteredUsers, () => {
  highlightedIndex.value = 0;
});

watch(userSearch, () => {
  if (syncingSearchFromSelection.value) {
    return;
  }

  const selected = users.value.find((user) => user.uuid === userIdentifier.value);
  if (selected && userSearch.value !== formatUser(selected)) {
    userIdentifier.value = '';
  }
});

function userLabel(user) {
  return user.full_name || user.name || 'Unnamed user';
}

function formatUser(user) {
  return `${userLabel(user)} — ${user.email}`;
}

function optionClass(user, index) {
  if (index === highlightedIndex.value) {
    return 'bg-brand-50 text-brand-800';
  }
  if (user.uuid === userIdentifier.value) {
    return 'bg-slate-50';
  }
  return 'hover:bg-slate-50';
}

function openDropdown() {
  dropdownOpen.value = true;
}

function closeDropdown() {
  dropdownOpen.value = false;
}

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value;
}

function selectUser(user) {
  userIdentifier.value = user.uuid;
  syncingSearchFromSelection.value = true;
  userSearch.value = formatUser(user);
  syncingSearchFromSelection.value = false;
  closeDropdown();
}

function selectHighlighted() {
  const user = filteredUsers.value[highlightedIndex.value];
  if (user) {
    selectUser(user);
  }
}

function moveHighlight(step) {
  if (!dropdownOpen.value) {
    openDropdown();
    return;
  }

  const total = filteredUsers.value.length;
  if (!total) {
    return;
  }

  highlightedIndex.value = (highlightedIndex.value + step + total) % total;
}

function onDocumentClick(event) {
  if (!comboboxRoot.value?.contains(event.target)) {
    closeDropdown();

    const selected = users.value.find((user) => user.uuid === userIdentifier.value);
    if (selected) {
      syncingSearchFromSelection.value = true;
      userSearch.value = formatUser(selected);
      syncingSearchFromSelection.value = false;
    }
  }
}

async function loadAllUsers() {
  loadingUsers.value = true;
  const collected = [];
  let page = 1;
  let lastPage = 1;

  try {
    do {
      const { data } = await userService.list({
        page,
        per_page: 100,
        sort_by: 'full_name',
        sort_dir: 'asc',
      });

      const payload = data.data?.users ?? {};
      collected.push(...(payload.items ?? []));
      lastPage = payload.meta?.last_page ?? page;
      page += 1;
    } while (page <= lastPage);

    users.value = collected;
  } finally {
    loadingUsers.value = false;
  }
}

onMounted(async () => {
  document.addEventListener('mousedown', onDocumentClick);

  try {
    await Promise.all([rolesStore.fetchRoles({ per_page: 100 }), loadAllUsers()]);
  } catch (err) {
    error.value = err?.message || 'Unable to load users';
  }
});

onUnmounted(() => {
  document.removeEventListener('mousedown', onDocumentClick);
});

async function onAssign() {
  error.value = '';
  try {
    await rolesStore.assignUserRoles(userIdentifier.value, [selectedRole.value]);
  } catch (err) {
    error.value = err?.message || rolesStore.error || 'Unable to assign roles';
  }
}
</script>
