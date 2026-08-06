<template>
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-slate-500">
        <template v-for="(item, index) in items" :key="item.label">
            <span v-if="index > 0" class="text-slate-300">/</span>
            <RouterLink
                v-if="item.to && index < items.length - 1"
                :to="item.to"
                class="hover:text-slate-800"
            >
                {{ item.label }}
            </RouterLink>
            <span v-else class="font-medium text-slate-800">{{ item.label }}</span>
        </template>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const items = computed(() => {
    const crumbs = [{ label: 'Home', to: { name: 'dashboard' } }];

    if (route.meta.title) {
        crumbs.push({ label: String(route.meta.title) });
    }

    return crumbs;
});
</script>
