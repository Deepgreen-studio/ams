<template>
  <div class="rounded-2xl bg-white p-5 ring-1 ring-zinc-100">
    <div class="mb-6 flex items-center justify-between gap-3">
      <h2 class="text-base font-semibold text-zinc-900">Team workload</h2>
      <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs text-zinc-600">
        Open work
      </span>
    </div>

    <div v-if="!people.length" class="py-10 text-center text-sm text-zinc-500">
      No assigned workload yet.
    </div>

    <div v-else class="flex items-end justify-between gap-2 overflow-x-auto pb-1 pt-2">
      <div
        v-for="person in people"
        :key="person.uuid"
        class="flex min-w-[3.25rem] flex-col items-center gap-2"
      >
        <div class="flex flex-col-reverse items-center gap-1.5">
          <span
            v-for="(dot, index) in person.dots"
            :key="`${person.uuid}-${index}`"
            class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-bold"
            :class="dotClass(dot)"
          >
            <template v-if="index === person.dots.length - 1 && person.open_count">
              {{ String(person.open_count).padStart(2, '0') }}
            </template>
          </span>
        </div>
        <p class="max-w-[4.5rem] truncate text-center text-xs font-medium text-zinc-600">
          {{ shortName(person) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  payload: {
    type: Object,
    default: null,
  },
});

const people = computed(() =>
  (props.payload?.people ?? []).map((person) => ({
    ...person,
    dots: dotsFor(person.open_count),
  })),
);

function dotsFor(count) {
  const n = Math.max(1, Math.min(6, Number(count) || 0));
  const dots = Array.from({ length: n }, (_, i) => (i === n - 1 ? 'filled' : 'outline'));
  if ((Number(count) || 0) >= 6) {
    dots[dots.length - 1] = 'accent';
  }
  return dots;
}

function shortName(person) {
  const first = String(person.first_name || '').trim();
  if (first) return first;
  const full = String(person.full_name || '').trim();
  return full.split(/\s+/)[0] || 'User';
}

function dotClass(type) {
  if (type === 'accent') {
    return 'bg-brand-500 text-white';
  }
  if (type === 'filled') {
    return 'bg-zinc-900 text-white';
  }
  return 'border-2 border-zinc-300 bg-white text-transparent';
}
</script>
