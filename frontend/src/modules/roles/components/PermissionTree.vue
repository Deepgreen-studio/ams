<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <input
        v-model="search"
        type="search"
        placeholder="Filter permissions..."
        class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 sm:max-w-sm"
      />
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50" @click="selectAll">
          Select all
        </button>
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50" @click="clearAll">
          Clear
        </button>
      </div>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <div v-else class="space-y-4">
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
        title="No permissions match"
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
  const names = props.groups.flatMap((group) => (group.permissions || []).map((permission) => permission.name));
  emit('update:selected', [...new Set(names)]);
}

function clearAll() {
  emit('update:selected', []);
}
</script>
