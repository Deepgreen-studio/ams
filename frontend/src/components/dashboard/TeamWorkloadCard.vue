<template>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-100">
    <div class="mb-6 flex items-center justify-between gap-3">
      <h2 class="text-base font-semibold text-zinc-900">Team workload</h2>
      <select v-model="range" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs text-zinc-600 outline-none focus:border-brand-500">
        <option>Last 3 months</option>
        <option>Last 30 days</option>
        <option>This week</option>
      </select>
    </div>

    <div class="flex items-end justify-between gap-2 overflow-x-auto pb-1 pt-2">
      <div
        v-for="person in people"
        :key="person.name"
        class="flex min-w-[3.25rem] flex-col items-center gap-2"
      >
        <div class="flex flex-col-reverse items-center gap-1.5">
          <span
            v-for="(dot, index) in person.dots"
            :key="`${person.name}-${index}`"
            class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-bold"
            :class="dotClass(dot, index === person.dots.length - 1)"
          >
            <template v-if="index === person.dots.length - 1 && person.count">
              {{ String(person.count).padStart(2, '0') }}
            </template>
          </span>
        </div>
        <p class="text-xs font-medium text-zinc-600">{{ person.name }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const range = ref('Last 3 months');

const people = [
  { name: 'Sam', count: 8, dots: ['outline', 'outline', 'outline', 'filled'] },
  { name: 'Meldy', count: 6, dots: ['outline', 'outline', 'filled'] },
  { name: 'Ken', count: 4, dots: ['outline', 'outline', 'outline', 'outline'] },
  { name: 'Diu', count: 9, dots: ['outline', 'outline', 'outline', 'accent'] },
  { name: 'Rena', count: 5, dots: ['outline', 'outline', 'filled'] },
  { name: 'Alex', count: 7, dots: ['outline', 'outline', 'outline', 'filled'] },
];

function dotClass(type, isTop) {
  if (type === 'accent') {
    return 'bg-brand-500 text-white';
  }
  if (type === 'filled') {
    return 'bg-zinc-900 text-white';
  }
  return isTop ? 'border-2 border-zinc-300 bg-white text-transparent' : 'border-2 border-zinc-300 bg-white text-transparent';
}
</script>
