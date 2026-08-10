<template>
  <div class="rounded-2xl bg-white p-5 ring-1 ring-zinc-100">
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

    <ul v-if="visibleTasks.length" class="space-y-1">
      <li
        v-for="task in visibleTasks"
        :key="`${task.type}-${task.id}`"
        class="flex items-center gap-3 rounded-xl px-1 py-2.5 hover:bg-zinc-50"
      >
        <span
          class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
          :class="
            task.done
              ? 'border-brand-500 bg-brand-500 text-white'
              : 'border-zinc-300 text-transparent'
          "
          aria-hidden="true"
        >
          <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none">
            <path d="M2.5 6.5L5 9L9.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </span>
        <RouterLink
          :to="taskLink(task)"
          class="min-w-0 flex-1 truncate text-sm hover:text-brand-600"
          :class="task.done ? 'text-zinc-400 line-through' : 'text-zinc-800'"
        >
          {{ task.title }}
        </RouterLink>
        <span
          class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
          :class="taskStatusClass(task.status)"
        >
          {{ task.status_label || task.status }}
        </span>
      </li>
    </ul>
    <p v-else class="py-8 text-center text-sm text-zinc-500">No tasks for today.</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  payload: {
    type: Object,
    default: null,
  },
});

const activeTab = ref('all');

const tabs = computed(() => {
  const counts = props.payload?.tabs || {};
  return [
    { id: 'all', label: 'All', count: counts.all ?? 0 },
    { id: 'important', label: 'Important', count: counts.important ?? 0 },
    { id: 'tickets', label: 'Tickets', count: counts.tickets ?? 0 },
    { id: 'customer_tasks', label: 'Tasks', count: counts.customer_tasks ?? 0 },
  ];
});

const items = computed(() => props.payload?.items ?? []);

const visibleTasks = computed(() => {
  if (activeTab.value === 'important') {
    return items.value.filter((t) => t.important);
  }
  if (activeTab.value === 'tickets') {
    return items.value.filter((t) => t.type === 'support_ticket');
  }
  if (activeTab.value === 'customer_tasks') {
    return items.value.filter((t) => t.type === 'customer_task');
  }
  return items.value;
});

function taskLink(task) {
  if (task.type === 'support_ticket') {
    return { name: 'support.tickets.show', params: { id: task.id } };
  }
  if (task.customer_uuid) {
    return { name: 'customers.show', params: { id: task.customer_uuid } };
  }
  return { name: 'customers.index' };
}

function taskStatusClass(status) {
  const map = {
    completed: 'bg-emerald-50 text-emerald-700',
    open: 'bg-sky-50 text-sky-700',
    in_progress: 'bg-orange-50 text-orange-700',
    pending: 'bg-amber-50 text-amber-700',
    waiting_for_customer: 'bg-violet-50 text-violet-700',
    reopened: 'bg-rose-50 text-rose-700',
    resolved: 'bg-emerald-50 text-emerald-700',
    closed: 'bg-zinc-100 text-zinc-600',
    cancelled: 'bg-zinc-100 text-zinc-500',
  };
  return map[String(status || '').toLowerCase()] || 'bg-zinc-100 text-zinc-600';
}
</script>
