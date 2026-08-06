<template>
  <div>
    <PageHeader
      title="Assign roles to user"
      description="Sync one or more roles onto a platform user."
    />

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

    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">User</label>
          <div ref="comboboxRoot" class="relative">
            <input
              v-model="userSearch"
              type="text"
              role="combobox"
              :aria-expanded="dropdownOpen"
              aria-controls="user-combobox-list"
              aria-autocomplete="list"
              placeholder="Search and select a user..."
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 pr-9 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
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
              class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
              tabindex="-1"
              :aria-label="dropdownOpen ? 'Close user list' : 'Open user list'"
              @click="toggleDropdown"
            >
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                  fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>

            <ul
              v-if="dropdownOpen"
              id="user-combobox-list"
              role="listbox"
              class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
            >
              <li v-if="loadingUsers" class="px-3 py-2 text-sm text-slate-500">Loading users...</li>
              <li v-else-if="!filteredUsers.length" class="px-3 py-2 text-sm text-slate-500">
                No users found.
              </li>
              <li
                v-for="(user, index) in filteredUsers"
                v-else
                :key="user.uuid"
                role="option"
                :aria-selected="user.uuid === userIdentifier"
                class="cursor-pointer px-3 py-2 text-sm"
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
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Roles</label>
          <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
            <label
              v-for="role in rolesStore.roles"
              :key="role.uuid"
              class="flex items-center gap-2 text-sm text-slate-700"
            >
              <input v-model="selectedRoles" type="checkbox" :value="role.uuid" />
              <span
                >{{ role.display_name }}
                <span class="text-xs text-slate-400">({{ role.name }})</span></span
              >
            </label>
          </div>
        </div>
      </div>

      <div class="mt-6 flex justify-end">
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="rolesStore.saving || !userIdentifier || !selectedRoles.length"
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
import PageHeader from '@/components/ui/PageHeader.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';
import { userService } from '@/modules/users/services/userService';

const rolesStore = useRolesStore();
const users = ref([]);
const userIdentifier = ref('');
const userSearch = ref('');
const selectedRoles = ref([]);
const error = ref('');
const loadingUsers = ref(false);
const dropdownOpen = ref(false);
const highlightedIndex = ref(0);
const comboboxRoot = ref(null);
const syncingSearchFromSelection = ref(false);

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
    await rolesStore.assignUserRoles(userIdentifier.value, selectedRoles.value);
  } catch (err) {
    error.value = err?.message || rolesStore.error || 'Unable to assign roles';
  }
}
</script>
