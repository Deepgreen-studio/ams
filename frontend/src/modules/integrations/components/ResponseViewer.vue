<template>
  <div class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Response</h3>
        <p class="mt-1 text-xs text-slate-500">
          Status, timing, headers, and body from the API Connection Engine.
        </p>
      </div>
      <div v-if="response" class="flex flex-wrap gap-2 text-xs">
        <span
          class="rounded-md px-2 py-1 font-medium ring-1 ring-inset"
          :class="
            response.successful
              ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
              : 'bg-rose-50 text-rose-700 ring-rose-600/20'
          "
        >
          {{ response.successful ? 'Success' : 'Failed' }}
        </span>
        <span class="rounded-md bg-slate-50 px-2 py-1 text-slate-700 ring-1 ring-slate-500/20"
          >HTTP {{ response.status_code }}</span
        >
        <span class="rounded-md bg-slate-50 px-2 py-1 text-slate-700 ring-1 ring-slate-500/20"
          >{{ response.duration_ms }} ms</span
        >
        <span class="rounded-md bg-slate-50 px-2 py-1 text-slate-700 ring-1 ring-slate-500/20"
          >{{ response.attempts }} attempt(s)</span
        >
      </div>
    </div>

    <div
      v-if="!response"
      class="mt-4 rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500"
    >
      No response yet. Run a connection test or request.
    </div>

    <div v-else class="mt-4 space-y-4">
      <div
        v-if="response.error"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ response.error }}
      </div>
      <div>
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Headers</h4>
        <pre class="max-h-40 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{
          formatJson(response.headers)
        }}</pre>
      </div>
      <div>
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Body</h4>
        <pre class="max-h-80 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{
          formatBody(response)
        }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  response: { type: Object, default: null },
});

function formatJson(value) {
  try {
    return JSON.stringify(value ?? {}, null, 2);
  } catch {
    return String(value);
  }
}

function formatBody(response) {
  if (response.raw_body) return response.raw_body;
  return formatJson(response.body);
}
</script>
