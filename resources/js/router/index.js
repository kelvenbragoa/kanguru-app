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
            path: '/termos',
            name: 'termos',
            component: () => import('@/views/pages/Termos.vue'),
            meta: { public: true },
        },
        {
            path: '/privacidade',
            name: 'privacidade',
            component: () => import('@/views/pages/Privacidade.vue'),
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
                    path: 'exceptions',
                    name: 'exceptions.index',
                    component: () => import('@/views/admin/exceptions/Index.vue'),
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
                    path: 'finance',
                    name: 'finance.index',
                    component: () => import('@/views/admin/finance/Index.vue'),
                },
                {
                    path: 'driver-kyc',
                    name: 'driverKyc.index',
                    component: () => import('@/views/admin/driver-kyc/Index.vue'),
                },
                {
                    path: 'driver-wallets',
                    name: 'driverWallets.index',
                    component: () => import('@/views/admin/driver-wallets/Index.vue'),
                },
                {
                    path: 'lost-items',
                    name: 'lostItems.index',
                    component: () => import('@/views/admin/lost-items/Index.vue'),
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
            path: '/business',
            component: AppLayout,
            meta: { requiresAuth: true, requiresBusiness: true },
            children: [
                {
                    path: '',
                    name: 'business.dashboard',
                    component: () => import('@/views/business/Dashboard.vue'),
                },
                {
                    path: 'orders',
                    name: 'business.orders',
                    component: () => import('@/views/business/orders/Index.vue'),
                },
                {
                    path: 'orders/:id',
                    name: 'business.orders.show',
                    component: () => import('@/views/business/orders/Show.vue'),
                },
                {
                    path: 'products',
                    name: 'business.products',
                    component: () => import('@/views/business/products/Index.vue'),
                },
                {
                    path: 'products/create',
                    name: 'business.products.create',
                    component: () => import('@/views/business/products/Form.vue'),
                },
                {
                    path: 'products/:id/edit',
                    name: 'business.products.edit',
                    component: () => import('@/views/business/products/Form.vue'),
                },
                {
                    path: 'rooms',
                    name: 'business.rooms',
                    component: () => import('@/views/business/rooms/Index.vue'),
                },
                {
                    path: 'rooms/create',
                    name: 'business.rooms.create',
                    component: () => import('@/views/business/rooms/Form.vue'),
                },
                {
                    path: 'rooms/:id/edit',
                    name: 'business.rooms.edit',
                    component: () => import('@/views/business/rooms/Form.vue'),
                },
                {
                    path: 'bookings',
                    name: 'business.bookings',
                    component: () => import('@/views/business/bookings/Index.vue'),
                },
                {
                    path: 'bookings/:id',
                    name: 'business.bookings.show',
                    component: () => import('@/views/business/bookings/Show.vue'),
                },
                {
                    path: 'services',
                    name: 'business.services',
                    component: () => import('@/views/business/services/Index.vue'),
                },
                {
                    path: 'services/create',
                    name: 'business.services.create',
                    component: () => import('@/views/business/services/Form.vue'),
                },
                {
                    path: 'services/:id/edit',
                    name: 'business.services.edit',
                    component: () => import('@/views/business/services/Form.vue'),
                },
                {
                    path: 'service-bookings',
                    name: 'business.serviceBookings',
                    component: () => import('@/views/business/service-bookings/Index.vue'),
                },
                {
                    path: 'service-bookings/:id',
                    name: 'business.serviceBookings.show',
                    component: () => import('@/views/business/service-bookings/Show.vue'),
                },
                {
                    path: 'earnings',
                    name: 'business.earnings',
                    component: () => import('@/views/business/earnings/Index.vue'),
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
    const { isAuthenticated, isStaff, isBusiness, user, fetchUser } = useAuth();

    if (isAuthenticated.value && !user.value) {
        await fetchUser();
    }

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.requiresStaff && !isStaff.value) {
        return isBusiness.value ? { name: 'business.dashboard' } : { name: 'home' };
    }

    if (to.meta.requiresBusiness && !isBusiness.value) {
        return isStaff.value ? { name: 'dashboard' } : { name: 'home' };
    }

    if (to.meta.guest && isAuthenticated.value) {
        if (isStaff.value) return { name: 'dashboard' };
        if (isBusiness.value) return { name: 'business.dashboard' };
    }

    return true;
});

export default router;
