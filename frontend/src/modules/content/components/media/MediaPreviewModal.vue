<template>
  <div v-if="open" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center" @click.self="$emit('close')">
    <div class="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">{{ item?.original_name || 'Preview' }}</h3>
          <p class="text-xs text-slate-500">{{ item?.type }} · {{ item?.human_size }} · v{{ item?.version }}</p>
        </div>
        <button type="button" class="rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100" @click="$emit('close')">Close</button>
      </div>
      <div class="max-h-[70vh] overflow-auto p-5">
        <img v-if="item?.is_image" :src="item.url" :alt="item.alt_text || item.name" class="mx-auto max-h-[55vh] rounded-lg object-contain" />
        <video v-else-if="item?.type === 'video'" :src="item.url" controls class="mx-auto max-h-[55vh] w-full rounded-lg bg-black" />
        <iframe v-else-if="item?.extension === 'pdf'" :src="item.url" class="h-[55vh] w-full rounded-lg border border-slate-200" />
        <div v-else class="rounded-lg border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">
          Preview not available for this file type.
          <a :href="item?.url" target="_blank" rel="noopener" class="mt-3 block font-medium text-brand-700">Open file</a>
        </div>
        <dl class="mt-5 grid gap-3 md:grid-cols-2">
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Name</dt>
            <dd class="text-sm text-slate-800">{{ item?.name }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">MIME</dt>
            <dd class="text-sm text-slate-800">{{ item?.mime_type }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-xs uppercase tracking-wide text-slate-500">URL</dt>
            <dd class="break-all text-sm text-slate-800">{{ item?.url }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
  item: { type: Object, default: null },
});

defineEmits(['close']);
</script>
