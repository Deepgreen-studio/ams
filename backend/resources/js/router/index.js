import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AuthenticationLayout from '@/layouts/AuthenticationLayout.vue';
import BlankLayout from '@/layouts/BlankLayout.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: AdminLayout,
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/pages/DashboardPage.vue'),
                    meta: { title: 'Dashboard' },
                },
            ],
        },
        {
            path: '/auth',
            component: AuthenticationLayout,
            children: [
                {
                    path: 'placeholder',
                    name: 'auth.placeholder',
                    component: () => import('@/pages/AuthPlaceholderPage.vue'),
                    meta: { title: 'Authentication' },
                },
            ],
        },
        {
            path: '/blank',
            component: BlankLayout,
            children: [
                {
                    path: '',
                    name: 'blank',
                    component: () => import('@/pages/BlankPage.vue'),
                    meta: { title: 'Blank' },
                },
            ],
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: () => import('@/pages/NotFoundPage.vue'),
            meta: { title: 'Page Not Found' },
        },
    ],
});

router.afterEach((to) => {
    const baseTitle = import.meta.env.VITE_APP_NAME || 'AMS';
    document.title = to.meta.title ? `${to.meta.title} · ${baseTitle}` : baseTitle;
});

export default router;
