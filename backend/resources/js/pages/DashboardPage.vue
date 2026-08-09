<template>
    <div>
        <!-- <PageHeader
            title="Dashboard"
            description="Enterprise Application Management System foundation is ready."
        /> -->

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="card in cards"
                :key="card.title"
                class="rounded-xl border border-slate-200 bg-white p-5 "
            >
                <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ card.title }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ card.description }}</p>
            </div>
        </div>

        <div class="mt-6">
            <EmptyState
                title="No modules connected yet"
                description="Business domains are scaffolded and ready for Phase 1.2 implementation."
            />
        </div>
    </div>
</template>

<script setup>
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';

const cards = [
    {
        label: 'Architecture',
        title: 'DDD Modular Monolith',
        description: 'Domain-isolated backend structure with shared contracts and services.',
    },
    {
        label: 'API',
        title: 'REST API First',
        description: 'Versioned /api/v1 foundation with standardized JSON responses.',
    },
    {
        label: 'Frontend',
        title: 'Vue 3 SPA Shell',
        description: 'Layouts, navigation, and reusable UI states are in place.',
    },
];
</script>
