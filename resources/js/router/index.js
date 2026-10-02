import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior() {
        return { top: 0 };
    },
    routes: [
        {
            path: '/',
            name: 'home',
            component: () => import('@/views/pages/Home.vue'),
            meta: { public: true },
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/pages/auth/Login.vue'),
            meta: { public: true, guest: true },
        },
        {
            path: '/rastrear',
            name: 'track',
            component: () => import('@/views/pages/Track.vue'),
            meta: { public: true },
        },
        {
            path: '/admin',
            component: AppLayout,
            meta: { requiresAuth: true, requiresStaff: true },
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/views/admin/Dashboard.vue'),
                },
                {
                    path: 'orders',
                    name: 'orders.index',
                    component: () => import('@/views/admin/orders/Index.vue'),
                },
                {
                    path: 'orders/create',
                    name: 'orders.create',
                    component: () => import('@/views/admin/orders/Form.vue'),
                },
                {
                    path: 'orders/:id',
                    name: 'orders.show',
                    component: () => import('@/views/admin/orders/Show.vue'),
                },
                {
                    path: 'dispatch',
                    name: 'dispatch.index',
                    component: () => import('@/views/admin/dispatch/Index.vue'),
                },
                {
                    path: 'payments',
                    name: 'payments.index',
                    component: () => import('@/views/admin/payments/Index.vue'),
                },
                {
                    path: 'profile',
                    name: 'profile',
                    component: () => import('@/views/admin/profile/Index.vue'),
                },
                {
                    path: 'shops',
                    name: 'shops.index',
                    component: () => import('@/views/admin/shops/Index.vue'),
                },
                {
                    path: 'shops/create',
                    name: 'shops.create',
                    component: () => import('@/views/admin/shops/Form.vue'),
                },
                {
                    path: 'shops/:id/edit',
                    name: 'shops.edit',
                    component: () => import('@/views/admin/shops/Form.vue'),
                },
                {
                    path: 'products',
                    name: 'products.index',
                    component: () => import('@/views/admin/products/Index.vue'),
                },
                {
                    path: 'products/create',
                    name: 'products.create',
                    component: () => import('@/views/admin/products/Form.vue'),
                },
                {
                    path: 'products/:id/edit',
                    name: 'products.edit',
                    component: () => import('@/views/admin/products/Form.vue'),
                },
                {
                    path: 'categories',
                    name: 'categories.index',
                    component: () => import('@/views/admin/categories/Index.vue'),
                },
                {
                    path: 'users',
                    name: 'users.index',
                    component: () => import('@/views/admin/users/Index.vue'),
                },
                {
                    path: 'vehicles',
                    name: 'vehicles.index',
                    component: () => import('@/views/admin/vehicles/Index.vue'),
                },
                {
                    path: 'vehicles/create',
                    name: 'vehicles.create',
                    component: () => import('@/views/admin/vehicles/Form.vue'),
                },
                {
                    path: 'vehicles/:id/edit',
                    name: 'vehicles.edit',
                    component: () => import('@/views/admin/vehicles/Form.vue'),
                },
                {
                    path: 'scooters',
                    name: 'scooters.index',
                    component: () => import('@/views/admin/scooters/Index.vue'),
                },
            ],
        },
        {
            path: '/auth/login',
            redirect: '/login',
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'notfound',
            component: () => import('@/views/pages/NotFound.vue'),
        },
    ],
});

router.beforeEach(async (to) => {
    const { isAuthenticated, isStaff, user, fetchUser } = useAuth();

    if (isAuthenticated.value && !user.value) {
        await fetchUser();
    }

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.requiresStaff && !isStaff.value) {
        return { name: 'home' };
    }

    if (to.meta.guest && isAuthenticated.value && isStaff.value) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
