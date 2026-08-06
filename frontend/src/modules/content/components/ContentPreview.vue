<template>
  <div :class="embedded || bodyOnly ? '' : 'rounded-xl border border-slate-200 bg-white'">
    <div
      v-if="!embedded && !bodyOnly"
      class="flex items-center justify-between border-b border-slate-200 px-4 py-3"
    >
      <h3 class="text-sm font-semibold text-slate-900">{{ live ? 'Live preview' : 'Preview' }}</h3>
      <span class="text-xs uppercase tracking-wide text-slate-400">{{ formatLabel }}</span>
    </div>
    <div :class="embedded || bodyOnly ? 'space-y-4' : 'space-y-4 p-5'">
      <div v-if="embedded && !bodyOnly" class="flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          {{ live ? 'Live preview' : 'Preview' }}
        </p>
        <span
          class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-500"
        >
          {{ formatLabel }}
        </span>
      </div>

      <template v-if="!bodyOnly">
        <div v-if="featuredImage" class="overflow-hidden rounded-lg bg-slate-100">
          <img :src="featuredImage" alt="" class="max-h-48 w-full object-cover" />
        </div>

        <div>
          <h1 class="text-xl font-semibold leading-snug text-slate-900">
            {{ title || 'Untitled content' }}
          </h1>
          <p v-if="slug" class="mt-1 font-mono text-xs text-slate-500">/{{ slug }}</p>
        </div>

        <p v-if="summary" class="text-sm leading-relaxed text-slate-700">{{ summary }}</p>
        <p v-if="excerpt" class="text-sm italic leading-relaxed text-slate-500">{{ excerpt }}</p>
      </template>

      <div
        class="prose prose-sm max-w-none text-slate-700"
        :class="bodyOnly ? '' : 'border-t border-slate-100 pt-4'"
        v-html="safeHtml"
      />

      <div
        v-if="!embedded && !bodyOnly && (seoTitle || seoDescription || canonicalUrl)"
        class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600"
      >
        <p v-if="seoTitle"><span class="font-semibold">SEO title:</span> {{ seoTitle }}</p>
        <p v-if="seoDescription" class="mt-1">
          <span class="font-semibold">SEO description:</span> {{ seoDescription }}
        </p>
        <p v-if="keywords" class="mt-1">
          <span class="font-semibold">Keywords:</span> {{ keywords }}
        </p>
        <p v-if="canonicalUrl" class="mt-1">
          <span class="font-semibold">Canonical:</span> {{ canonicalUrl }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';

const props = defineProps({
  title: { type: String, default: '' },
  slug: { type: String, default: '' },
  summary: { type: String, default: '' },
  excerpt: { type: String, default: '' },
  body: { type: String, default: '' },
  bodyFormat: { type: String, default: 'rich' },
  featuredImage: { type: String, default: '' },
  seoTitle: { type: String, default: '' },
  seoDescription: { type: String, default: '' },
  keywords: { type: String, default: '' },
  canonicalUrl: { type: String, default: '' },
  live: { type: Boolean, default: false },
  embedded: { type: Boolean, default: false },
  bodyOnly: { type: Boolean, default: false },
});

const formatLabel = computed(() => {
  if (props.bodyFormat === 'markdown') return 'Markdown';
  if (props.bodyFormat === 'html') return 'HTML';
  return 'Rich Text';
});

const safeHtml = computed(() => {
  const raw = props.bodyFormat === 'markdown' ? marked.parse(props.body || '') : props.body || '';
  return DOMPurify.sanitize(raw);
});
</script>
