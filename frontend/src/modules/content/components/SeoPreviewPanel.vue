<template>
  <div class="space-y-4">
    <div v-if="showSearch">
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
        Google search preview
      </p>
      <div class="max-w-xl rounded-lg border border-slate-100 bg-slate-50/60 p-4">
        <p class="truncate text-sm text-emerald-800">{{ displayCanonical }}</p>
        <p class="mt-1 text-lg leading-snug text-[#1a0dab]">{{ displayTitle }}</p>
        <p class="mt-1 text-sm leading-snug text-slate-600">{{ displayDescription }}</p>
      </div>
      <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
        <span
          :class="titleLength > 60 ? 'font-medium text-amber-600' : ''"
        >
          Title {{ titleLength }}/60
        </span>
        <span
          :class="descriptionLength > 160 ? 'font-medium text-amber-600' : ''"
        >
          Description {{ descriptionLength }}/160
        </span>
      </div>
    </div>

    <div v-if="showSocial" class="space-y-4">
      <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Open Graph</p>
        <div class="overflow-hidden rounded-lg border border-slate-200">
          <div class="aspect-[1.91/1] bg-slate-100">
            <img
              v-if="ogDisplayImage"
              :src="ogDisplayImage"
              alt=""
              class="h-full w-full object-cover"
            />
            <div v-else class="flex h-full items-center justify-center text-xs text-slate-400">
              No OG image
            </div>
          </div>
          <div class="space-y-1 bg-slate-50 px-3 py-2.5">
            <p class="text-[10px] uppercase tracking-wide text-slate-400">{{ hostname }}</p>
            <p class="line-clamp-2 text-sm font-semibold text-slate-900">{{ resolvedOgTitle }}</p>
            <p class="line-clamp-2 text-xs text-slate-600">{{ resolvedOgDescription }}</p>
          </div>
        </div>
      </div>

      <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
          Twitter card
        </p>
        <div class="overflow-hidden rounded-xl border border-slate-200">
          <div class="aspect-[2/1] bg-slate-100">
            <img
              v-if="twitterDisplayImage"
              :src="twitterDisplayImage"
              alt=""
              class="h-full w-full object-cover"
            />
            <div v-else class="flex h-full items-center justify-center text-xs text-slate-400">
              No Twitter image
            </div>
          </div>
          <div class="space-y-1 px-3 py-2.5">
            <p class="line-clamp-2 text-sm font-semibold text-slate-900">{{ resolvedTwitterTitle }}</p>
            <p class="line-clamp-2 text-xs text-slate-600">{{ resolvedTwitterDescription }}</p>
            <p class="text-[10px] uppercase tracking-wide text-slate-400">
              {{ cardType }} · {{ hostname }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="showSchema" class="rounded-xl border border-slate-800 bg-slate-950 p-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
          Schema.org JSON-LD
        </p>
        <pre class="overflow-x-auto text-xs leading-relaxed text-emerald-300">{{
          schemaPreview
        }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  mode: { type: String, default: 'all' }, // all | search | social
  title: { type: String, default: '' },
  seoTitle: { type: String, default: '' },
  seoDescription: { type: String, default: '' },
  excerpt: { type: String, default: '' },
  summary: { type: String, default: '' },
  canonicalUrl: { type: String, default: '' },
  featuredImage: { type: String, default: '' },
  ogTitle: { type: String, default: '' },
  ogDescription: { type: String, default: '' },
  ogImage: { type: String, default: '' },
  twitterCard: { type: String, default: 'summary_large_image' },
  twitterTitle: { type: String, default: '' },
  twitterDescription: { type: String, default: '' },
  twitterImage: { type: String, default: '' },
  schemaType: { type: String, default: 'Article' },
  schemaJson: { type: [Object, Array, String], default: null },
  slug: { type: String, default: '' },
  typeSlug: { type: String, default: 'page' },
});

const showSearch = computed(() => ['all', 'search'].includes(props.mode));
const showSocial = computed(() => ['all', 'social'].includes(props.mode));
const showSchema = computed(() => props.mode === 'all' || props.mode === 'social');

const displayTitle = computed(() => props.seoTitle || props.title || 'Untitled content');
const displayDescription = computed(
  () =>
    props.seoDescription ||
    props.excerpt ||
    props.summary ||
    'Add a meta description to improve CTR.',
);
const displayCanonical = computed(() => {
  if (props.canonicalUrl) return props.canonicalUrl;
  return `https://example.com/content/${props.typeSlug || 'page'}/${props.slug || 'slug'}`;
});

const ogDisplayImage = computed(() => props.ogImage || props.featuredImage || '');
const twitterDisplayImage = computed(
  () => props.twitterImage || props.ogImage || props.featuredImage || '',
);
const resolvedOgTitle = computed(() => props.ogTitle || displayTitle.value);
const resolvedOgDescription = computed(() => props.ogDescription || displayDescription.value);
const resolvedTwitterTitle = computed(() => props.twitterTitle || resolvedOgTitle.value);
const resolvedTwitterDescription = computed(
  () => props.twitterDescription || resolvedOgDescription.value,
);
const cardType = computed(() => props.twitterCard || 'summary_large_image');
const titleLength = computed(() => displayTitle.value.length);
const descriptionLength = computed(() => displayDescription.value.length);

const hostname = computed(() => {
  try {
    return new URL(displayCanonical.value).hostname;
  } catch {
    return 'example.com';
  }
});

const schemaPreview = computed(() => {
  if (props.schemaJson && typeof props.schemaJson === 'object') {
    return JSON.stringify(props.schemaJson, null, 2);
  }
  if (typeof props.schemaJson === 'string' && props.schemaJson.trim()) {
    try {
      return JSON.stringify(JSON.parse(props.schemaJson), null, 2);
    } catch {
      return props.schemaJson;
    }
  }

  return JSON.stringify(
    {
      '@context': 'https://schema.org',
      '@type': props.schemaType || 'Article',
      headline: displayTitle.value,
      description: displayDescription.value,
      url: displayCanonical.value,
      image: ogDisplayImage.value ? [ogDisplayImage.value] : undefined,
    },
    null,
    2,
  );
});
</script>
