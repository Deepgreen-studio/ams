<template>
  <div class="rounded-xl border border-slate-200 bg-white">
    <button
      type="button"
      class="flex w-full items-center justify-between px-4 py-3 text-left"
      @click="open = !open"
    >
      <div>
        <p class="text-sm font-semibold text-slate-900">{{ group.name }}</p>
        <p class="text-xs text-slate-500">
          {{ group.module }} · {{ group.permissions?.length || 0 }} permissions
        </p>
      </div>
      <div class="flex items-center gap-3">
        <label class="inline-flex items-center gap-2 text-xs text-slate-600" @click.stop>
          <input
            type="checkbox"
            :checked="allSelected"
            :indeterminate.prop="partialSelected"
            @change="onGroupToggle"
          />
          Group
        </label>
        <span class="text-slate-400">{{ open ? '−' : '+' }}</span>
      </div>
    </button>

    <div v-if="open" class="border-t border-slate-100 px-4 py-3">
      <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
        <label
          v-for="permission in group.permissions || []"
          :key="permission.id || permission.name"
          class="flex items-start gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm hover:bg-slate-50"
        >
          <input
            type="checkbox"
            class="mt-0.5"
            :checked="selected.includes(permission.name)"
            @change="$emit('toggle', permission.name)"
          />
          <span>
            <span class="block font-medium text-slate-800">{{
              permission.display_name || permission.name
            }}</span>
            <span class="block text-xs text-slate-500">{{ permission.name }}</span>
          </span>
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
});

const emit = defineEmits(['toggle', 'toggle-group']);
const open = ref(true);

const names = computed(() => (props.group.permissions || []).map((permission) => permission.name));
const allSelected = computed(
  () => names.value.length > 0 && names.value.every((name) => props.selected.includes(name)),
);
const partialSelected = computed(
  () => !allSelected.value && names.value.some((name) => props.selected.includes(name)),
);

function onGroupToggle(event) {
  emit('toggle-group', props.group, event.target.checked);
}
</script>
