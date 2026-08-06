<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center" @click.self="$emit('close')">
    <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">Choose from media library</h3>
          <p class="text-xs text-slate-500">Select an image already in the CMS library.</p>
        </div>
        <button type="button" class="rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100" @click="$emit('close')">Close</button>
      </div>
      <div class="border-b border-slate-200 px-5 py-3">
        <input
          v-model="search"
          type="search"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          placeholder="Search images…"
          @keyup.enter="load"
        />
      </div>
      <div class="flex-1 overflow-auto p-5">
        <div v-if="loading" class="grid gap-3 sm:grid-cols-3">
          <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-lg bg-slate-100" />
        </div>
        <div v-else class="grid gap-3 sm:grid-cols-3">
          <button
            v-for="item in items"
            :key="item.uuid"
            type="button"
            class="overflow-hidden rounded-lg border border-slate-200 text-left hover:border-brand-400"
            @click="select(item)"
          >
            <img :src="item.url" :alt="item.alt_text || item.name" class="h-28 w-full object-cover" />
            <p class="truncate px-2 py-1.5 text-xs text-slate-700">{{ item.original_name }}</p>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { mediaLibraryService } from '@/modules/content/services/mediaLibraryService';

const props = defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'select']);

const loading = ref(false);
const items = ref([]);
const search = ref('');

watch(() => props.open, (value) => {
  if (value) load();
});

onMounted(() => {
  if (props.open) load();
});

async function load() {
  loading.value = true;
  try {
    const { data } = await mediaLibraryService.list({
      type: 'image',
      search: search.value,
      per_page: 24,
    });
    items.value = data.data?.media?.items ?? [];
  } finally {
    loading.value = false;
  }
}

function select(item) {
  emit('select', item);
  emit('close');
}
</script>
