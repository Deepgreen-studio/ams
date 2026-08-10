<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <input
        v-model="search"
        type="search"
        placeholder="Search permissions..."
        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0 sm:max-w-xs"
      />
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50"
          @click="selectAll"
        >
          Select all
        </button>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50"
          @click="clearAll"
        >
          Clear
        </button>
      </div>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-20 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else class="max-h-[32rem] space-y-3 overflow-y-auto pr-1">
      <PermissionGroup
        v-for="group in filteredGroups"
        :key="group.uuid || group.id"
        :group="group"
        :selected="selected"
        @toggle="togglePermission"
        @toggle-group="toggleGroup"
      />
      <EmptyState
        v-if="!filteredGroups.length"
        title="No permissions found"
        description="Try a different search term."
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import PermissionGroup from '@/modules/roles/components/PermissionGroup.vue';

const props = defineProps({
  groups: { type: Array, default: () => [] },
  selected: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:selected']);

const search = ref('');

const filteredGroups = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) {
    return props.groups;
  }

  return props.groups
    .map((group) => {
      const permissions = (group.permissions || []).filter((permission) => {
        return (
          permission.name?.toLowerCase().includes(term) ||
          permission.display_name?.toLowerCase().includes(term) ||
          group.name?.toLowerCase().includes(term)
        );
      });

      return { ...group, permissions };
    })
    .filter((group) => group.permissions?.length);
});

function togglePermission(name) {
  const next = props.selected.includes(name)
    ? props.selected.filter((item) => item !== name)
    : [...props.selected, name];
  emit('update:selected', next);
}

function toggleGroup(group, checked) {
  const names = (group.permissions || []).map((permission) => permission.name);
  let next = [...props.selected];

  if (checked) {
    next = [...new Set([...next, ...names])];
  } else {
    next = next.filter((name) => !names.includes(name));
  }

  emit('update:selected', next);
}

function selectAll() {
  const names = props.groups.flatMap((group) =>
    (group.permissions || []).map((permission) => permission.name)
  );
  emit('update:selected', [...new Set(names)]);
}

function clearAll() {
  emit('update:selected', []);
}
</script>
