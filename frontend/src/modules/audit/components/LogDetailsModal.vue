<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex justify-end bg-slate-900/40"
    @click.self="$emit('close')"
  >
    <aside class="flex h-full w-full max-w-lg flex-col bg-white shadow-xl">
      <div class="flex items-start justify-between gap-3 border-b border-zinc-100 px-6 py-5">
        <div class="min-w-0">
          <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
          <p v-if="heading" class="mt-0.5 truncate text-sm text-slate-500">{{ heading }}</p>
        </div>
        <button
          type="button"
          class="shrink-0 rounded-[12px] border border-zinc-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-6">
        <template v-if="isLoginHistory">
          <div class="mb-5">
            <StatusBadge v-if="item?.status" :status="item.status" />
          </div>

          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">User</dt>
              <dd class="mt-1 text-sm text-slate-900">
                {{ item?.user?.full_name || item?.user?.email || 'Unknown user' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ item?.user?.email || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Logged in</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ formatDate(item?.login_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Logged out</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ formatDate(item?.logout_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Duration</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ formatDuration(item?.duration_seconds) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">IP address</dt>
              <dd class="mt-1 font-mono text-sm text-slate-900">{{ item?.ip_address || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Browser</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ item?.browser || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Operating system</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ item?.operating_system || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Device</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ item?.device || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Location</dt>
              <dd class="mt-1 text-sm text-slate-900">
                {{ [item?.city, item?.country].filter(Boolean).join(', ') || '—' }}
              </dd>
            </div>
          </dl>
        </template>

        <template v-else-if="isAuditDiff">
          <div class="mb-5 flex flex-wrap items-center gap-2">
            <StatusBadge v-if="item?.action" :status="item.action" />
            <span
              v-if="item?.module"
              class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700"
            >
              {{ formatLabel(item.module) }}
            </span>
          </div>

          <dl class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Actor</dt>
              <dd class="mt-1 text-sm text-slate-900">
                {{ item?.user?.full_name || item?.user?.email || 'System' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">When</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ formatDate(item?.created_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Company</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ item?.company?.company_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">IP address</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ item?.ip_address || '—' }}</dd>
            </div>
            <div v-if="item?.reason" class="sm:col-span-2">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Reason</dt>
              <dd class="mt-1 text-sm text-slate-900">{{ item.reason }}</dd>
            </div>
          </dl>

          <div v-if="diffRows.length">
            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
              Field changes
            </h4>
            <div class="overflow-hidden rounded-[12px] ring-1 ring-zinc-100">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="border-b border-zinc-100 bg-zinc-50/80">
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-zinc-500">Field</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-zinc-500">Before</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-zinc-500">After</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in diffRows"
                    :key="row.key"
                    class="border-b border-zinc-50 last:border-0"
                    :class="row.changed ? 'bg-amber-50/40' : ''"
                  >
                    <td class="px-4 py-2.5 font-medium text-slate-800">{{ formatLabel(row.key) }}</td>
                    <td class="px-4 py-2.5 text-slate-500">
                      <span class="break-all">{{ row.before }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-slate-800">
                      <span class="break-all">{{ row.after }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <p v-else class="rounded-[12px] bg-zinc-50 px-4 py-3 text-sm text-slate-500">
            No field-level before/after values were recorded for this change.
          </p>
        </template>

        <pre
          v-else
          class="overflow-x-auto rounded-[12px] bg-slate-950 p-4 text-xs leading-relaxed text-slate-100"
        >{{ pretty }}</pre>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '@/modules/audit/components/StatusBadge.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  item: { type: Object, default: null },
  title: { type: String, default: 'Log details' },
  subtitle: { type: String, default: '' },
});
defineEmits(['close']);

const pretty = computed(() => JSON.stringify(props.item || {}, null, 2));

const heading = computed(() => {
  if (props.subtitle) {
    return formatLabel(props.subtitle);
  }
  return '';
});

const isLoginHistory = computed(() => Boolean(props.item?.login_at) && !isAuditDiff.value);

const isAuditDiff = computed(() => {
  const item = props.item;
  if (!item) {
    return false;
  }

  return item.before_data != null || item.after_data != null || Array.isArray(item.changed_fields);
});

const diffRows = computed(() => {
  const item = props.item || {};
  const before = asObject(item.before_data);
  const after = asObject(item.after_data);
  const changed = Array.isArray(item.changed_fields) ? item.changed_fields : [];
  const keys = [...new Set([...Object.keys(before), ...Object.keys(after), ...changed])];

  return keys.map((key) => {
    const beforeValue = formatValue(before[key]);
    const afterValue = formatValue(after[key]);

    return {
      key,
      before: beforeValue,
      after: afterValue,
      changed: beforeValue !== afterValue || changed.includes(key),
    };
  });
});

function asObject(value) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return {};
  }
  return value;
}

function formatValue(value) {
  if (value == null || value === '') {
    return '—';
  }
  if (typeof value === 'object') {
    return JSON.stringify(value);
  }
  return String(value);
}

function formatLabel(value) {
  if (!value) {
    return '';
  }

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDate(value) {
  if (!value) {
    return '—';
  }
  return new Date(value).toLocaleString();
}

function formatDuration(seconds) {
  const total = Number(seconds);
  if (!Number.isFinite(total) || total < 0) {
    return '—';
  }

  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const remaining = Math.floor(total % 60);

  if (hours) {
    return `${hours}h ${minutes}m`;
  }
  if (minutes) {
    return `${minutes}m ${remaining}s`;
  }
  return `${remaining}s`;
}
</script>
