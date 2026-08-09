<template>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-100">
    <div class="mb-4 flex items-center justify-between gap-3">
      <h2 class="text-base font-semibold text-zinc-900">Today’s tasks</h2>
    </div>

    <div class="mb-4 flex flex-wrap gap-1 border-b border-zinc-100 pb-1">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="rounded-full px-3 py-1.5 text-sm font-medium transition"
        :class="
          activeTab === tab.id
            ? 'bg-zinc-900 text-white'
            : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-800'
        "
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
        <span
          v-if="tab.count != null"
          class="ml-1 inline-flex min-w-[1.25rem] justify-center rounded-full px-1 text-[10px]"
          :class="activeTab === tab.id ? 'bg-white/20 text-white' : 'bg-zinc-100 text-zinc-500'"
        >
          {{ tab.count }}
        </span>
      </button>
    </div>

    <ul class="space-y-1">
      <li
        v-for="task in visibleTasks"
        :key="task.id"
        class="flex items-center gap-3 rounded-xl px-1 py-2.5 hover:bg-zinc-50"
      >
        <button
          type="button"
          class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
          :class="
            task.done
              ? 'border-brand-500 bg-brand-500 text-white'
              : 'border-zinc-300 text-transparent hover:border-brand-400'
          "
          :aria-pressed="task.done"
          @click="task.done = !task.done"
        >
          <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
            <path d="M2.5 6.5L5 9L9.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <span
          class="min-w-0 flex-1 truncate text-sm"
          :class="task.done ? 'text-zinc-400 line-through' : 'text-zinc-800'"
        >
          {{ task.title }}
        </span>
        <span
          class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
          :class="taskStatusClass(task.status)"
        >
          {{ task.status }}
        </span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';

const activeTab = ref('all');

const tabs = [
  { id: 'all', label: 'All', count: 10 },
  { id: 'important', label: 'Important', count: null },
  { id: 'notes', label: 'Notes', count: 5 },
  { id: 'links', label: 'Links', count: 10 },
];

const tasks = reactive([
  { id: 1, title: 'Review Android release notes', status: 'Approved', done: true, important: true, tab: 'all' },
  { id: 2, title: 'Approve privacy policy draft', status: 'In review', done: false, important: true, tab: 'all' },
  { id: 3, title: 'Sync integration health checks', status: 'On going', done: false, important: false, tab: 'all' },
  { id: 4, title: 'Close overdue support tickets', status: 'On going', done: false, important: true, tab: 'all' },
  { id: 5, title: 'Update customer license pack', status: 'Approved', done: false, important: false, tab: 'all' },
  { id: 6, title: 'Draft compliance breach report', status: 'In review', done: false, important: true, tab: 'notes' },
]);

const visibleTasks = computed(() => {
  if (activeTab.value === 'important') {
    return tasks.filter((t) => t.important);
  }
  if (activeTab.value === 'notes') {
    return tasks.filter((t) => t.tab === 'notes' || t.status === 'In review');
  }
  if (activeTab.value === 'links') {
    return tasks.slice(0, 3);
  }
  return tasks;
});

function taskStatusClass(status) {
  const map = {
    Approved: 'bg-emerald-50 text-emerald-700',
    'In review': 'bg-rose-50 text-rose-700',
    'On going': 'bg-orange-50 text-orange-700',
  };
  return map[status] || 'bg-zinc-100 text-zinc-600';
}
</script>
