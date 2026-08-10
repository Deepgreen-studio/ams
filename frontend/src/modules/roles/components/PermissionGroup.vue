<template>
  <div class="overflow-hidden rounded-[12px] border border-slate-200 bg-white">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50"
      @click="open = !open"
    >
      <div class="min-w-0">
        <p class="text-sm font-semibold text-slate-900">{{ group.name }}</p>
        <p class="text-xs text-slate-500">
          {{ selectedCount }}/{{ names.length }} selected
        </p>
      </div>
      <div class="flex shrink-0 items-center gap-3">
        <label class="inline-flex items-center gap-2 text-xs text-slate-600" @click.stop>
          <input
            type="checkbox"
            class="h-4 w-4 accent-brand-600"
            :checked="allSelected"
            :indeterminate.prop="partialSelected"
            @change="onGroupToggle"
          />
          All
        </label>
        <span class="text-slate-400">{{ open ? '−' : '+' }}</span>
      </div>
    </button>

    <div v-if="open" class="border-t border-slate-100 px-3 py-2.5">
      <div class="grid gap-1 sm:grid-cols-2">
        <label
          v-for="permission in group.permissions || []"
          :key="permission.id || permission.name"
          class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm hover:bg-slate-50"
        >
          <input
            type="checkbox"
            class="h-4 w-4 shrink-0 accent-brand-600"
            :checked="selected.includes(permission.name)"
            @change="$emit('toggle', permission.name)"
          />
          <span class="text-slate-800">{{ permission.display_name || permission.name }}</span>
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  group: { type: Object, required: true },
  selected: { type: Array, default: () => [] },
  defaultOpen: { type: Boolean, default: true },
});

const emit = defineEmits(['toggle', 'toggle-group']);
const open = ref(props.defaultOpen);

const names = computed(() => (props.group.permissions || []).map((permission) => permission.name));
const selectedCount = computed(
  () => names.value.filter((name) => props.selected.includes(name)).length
);
const allSelected = computed(
  () => names.value.length > 0 && names.value.every((name) => props.selected.includes(name))
);
const partialSelected = computed(
  () => !allSelected.value && names.value.some((name) => props.selected.includes(name))
);

function onGroupToggle(event) {
  emit('toggle-group', props.group, event.target.checked);
}
</script>
