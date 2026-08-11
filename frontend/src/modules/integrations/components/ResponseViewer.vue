<template>
  <div class="rounded-[12px] bg-white p-6 sm:p-8">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-base font-semibold text-slate-900">Response</h3>
        <p class="mt-1 text-sm text-slate-500">
          Status, timing, headers, and body from the API Connection Engine.
        </p>
      </div>
      <div v-if="response" class="flex flex-wrap gap-2">
        <span
          class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
          :class="
            response.successful
              ? 'border-emerald-600 text-emerald-700'
              : 'border-rose-500 text-rose-700'
          "
        >
          <span
            class="h-1.5 w-1.5 rounded-full"
            :class="response.successful ? 'bg-emerald-600' : 'bg-rose-500'"
          />
          {{ response.successful ? 'Success' : 'Failed' }}
        </span>
        <span
          class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700"
        >
          HTTP {{ response.status_code }}
        </span>
        <span
          class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700"
        >
          {{ response.duration_ms }} ms
        </span>
        <span
          class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700"
        >
          {{ response.attempts }} attempt(s)
        </span>
      </div>
    </div>

    <div
      v-if="!response"
      class="mt-5 rounded-[12px] border border-dashed border-zinc-200 bg-zinc-50/60 px-4 py-10 text-center text-sm text-slate-500"
    >
      No response yet. Run a connection test or request.
    </div>

    <div v-else class="mt-5 space-y-5">
      <div
        v-if="response.error"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ response.error }}
      </div>
      <div>
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Headers</h4>
        <pre
          class="max-h-40 overflow-auto rounded-[12px] bg-slate-900 p-4 text-xs leading-relaxed text-slate-100"
          >{{ formatJson(response.headers) }}</pre
        >
      </div>
      <div>
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Body</h4>
        <pre
          class="max-h-80 overflow-auto rounded-[12px] bg-slate-900 p-4 text-xs leading-relaxed text-slate-100"
          >{{ formatBody(response) }}</pre
        >
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
