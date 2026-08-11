<template>
  <div class="grid gap-6 xl:grid-cols-12">
    <aside class="xl:col-span-4">
      <div class="rounded-[12px] bg-white p-6 sm:p-7">
        <div v-if="bannerSrc && !bannerFailed" class="mb-5 overflow-hidden rounded-[12px]">
          <img
            :src="bannerSrc"
            alt=""
            class="h-28 w-full object-cover"
            @error="bannerFailed = true"
          />
        </div>

        <div class="flex flex-col items-start gap-4">
          <div
            class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-lg font-semibold text-brand-700"
          >
            <img
              v-if="iconSrc && !iconFailed"
              :src="iconSrc"
              alt=""
              class="h-full w-full object-cover"
              @error="iconFailed = true"
            />
            <span v-else>{{ initials }}</span>
          </div>

          <div class="min-w-0 w-full">
            <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
              {{ application.name }}
            </h2>
            <p class="mt-1 truncate text-sm text-slate-500">{{ application.slug }}</p>
            <div class="mt-3 flex flex-wrap gap-1.5">
              <StatusBadge :status="application.platform" kind="platform" />
              <StatusBadge :status="application.status" />
              <StatusBadge :status="application.visibility" kind="visibility" />
            </div>
          </div>
        </div>

        <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5">
          <div
            v-for="item in sidebarItems"
            :key="item.label"
            class="flex items-start justify-between gap-3"
          >
            <dt class="text-sm text-zinc-500">{{ item.label }}</dt>
            <dd class="text-right text-sm font-medium text-slate-900">{{ item.value }}</dd>
          </div>
        </dl>
      </div>
    </aside>

    <section class="space-y-6 xl:col-span-8">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in metricCards"
          :key="card.label"
          class="rounded-[12px] bg-white px-4 py-4 ring-1 ring-zinc-100"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ card.label }}</p>
          <p class="mt-1.5 truncate text-lg font-semibold tracking-tight text-slate-900">
            {{ card.value }}
          </p>
        </div>
      </div>

      <div class="rounded-[12px] bg-white p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900">Profile</h3>
        <p class="mt-1 text-sm text-slate-500">Core identity and ownership for this application.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
          <div
            v-for="item in profileItems"
            :key="item.label"
            class="rounded-[12px] bg-zinc-50 px-4 py-3.5"
          >
            <p class="text-xs font-medium text-zinc-500">{{ item.label }}</p>
            <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ item.value }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-[12px] bg-white p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900">Versions & media</h3>
        <p class="mt-1 text-sm text-slate-500">Release baseline and optional media assets.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
          <div
            v-for="item in versionItems"
            :key="item.label"
            class="rounded-[12px] bg-zinc-50 px-4 py-3.5"
          >
            <p class="text-xs font-medium text-zinc-500">{{ item.label }}</p>
            <p class="mt-1 break-all text-sm font-semibold text-slate-900">
              <a
                v-if="item.href"
                :href="item.href"
                target="_blank"
                rel="noopener noreferrer"
                class="text-brand-600 hover:text-brand-700"
              >
                {{ item.value }}
              </a>
              <span v-else>{{ item.value }}</span>
            </p>
          </div>
        </div>

        <div class="mt-4 rounded-[12px] bg-zinc-50 px-4 py-3.5">
          <p class="text-xs font-medium text-zinc-500">Description</p>
          <p class="mt-1 whitespace-pre-wrap text-sm font-medium text-slate-900">
            {{ application.description || '—' }}
          </p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { formatDate } from '@/utils/formatters';
import { resolveMediaUrl } from '@/utils/mediaUrl';
import StatusBadge from '@/modules/applications/components/StatusBadge.vue';

const props = defineProps({
  application: { type: Object, required: true },
});

const iconFailed = ref(false);
const bannerFailed = ref(false);

const initials = computed(() =>
  String(props.application?.name || 'A')
    .trim()
    .slice(0, 2)
    .toUpperCase(),
);

const iconSrc = computed(() => resolveMediaUrl(props.application?.icon || ''));
const bannerSrc = computed(() => resolveMediaUrl(props.application?.banner || ''));

watch(iconSrc, () => {
  iconFailed.value = false;
});

watch(bannerSrc, () => {
  bannerFailed.value = false;
});

const sidebarItems = computed(() => [
  { label: 'Created', value: formatDate(props.application?.created_at) || '—' },
  { label: 'Updated', value: formatDate(props.application?.updated_at) || '—' },
  { label: 'Created by', value: props.application?.creator?.full_name || '—' },
  { label: 'Updated by', value: props.application?.updater?.full_name || '—' },
]);

const metricCards = computed(() => [
  {
    label: 'Platform',
    value: props.application?.platform === 'ios'
      ? 'iOS'
      : String(props.application?.platform || '—')
          .replaceAll('_', ' ')
          .replace(/\b\w/g, (c) => c.toUpperCase()),
  },
  { label: 'Version', value: props.application?.current_version || '—' },
  {
    label: 'Category',
    value: props.application?.category_label || props.application?.category || '—',
  },
  {
    label: 'Company',
    value: props.application?.company?.company_name || '—',
  },
]);

const profileItems = computed(() => [
  { label: 'Company', value: props.application?.company?.company_name || '—' },
  { label: 'Integration', value: props.application?.integration?.name || 'None' },
  {
    label: 'Category',
    value: props.application?.category_label || props.application?.category || '—',
  },
  {
    label: 'Slug',
    value: props.application?.slug || '—',
  },
]);

const versionItems = computed(() => {
  const items = [
    { label: 'Current version', value: props.application?.current_version || '—' },
    {
      label: 'Minimum supported',
      value: props.application?.minimum_supported_version || '—',
    },
  ];

  if (props.application?.icon) {
    items.push({
      label: 'Icon URL',
      value: props.application.icon,
      href: props.application.icon,
    });
  }
  if (props.application?.banner) {
    items.push({
      label: 'Banner URL',
      value: props.application.banner,
      href: props.application.banner,
    });
  }

  return items;
});
</script>
