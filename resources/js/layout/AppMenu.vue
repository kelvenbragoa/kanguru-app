<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AppMenuItem from './AppMenuItem.vue';

const route = useRoute();
const isBusiness = computed(() => route.path.startsWith('/business'));

const model = computed(() => {
    if (isBusiness.value) {
        return [
            {
                label: 'Kanguro Business',
                items: [{ label: 'Painel', icon: 'pi pi-fw pi-home', to: '/business' }],
            },
            {
                label: 'Operação',
                items: [
                    { label: 'Pedidos', icon: 'pi pi-fw pi-send', to: '/business/orders' },
                    { label: 'Inbox', icon: 'pi pi-fw pi-inbox', to: '/business/orders?inbox=1' },
                    { label: 'Produtos', icon: 'pi pi-fw pi-box', to: '/business/products' },
                    { label: 'Quartos', icon: 'pi pi-fw pi-building', to: '/business/rooms' },
                    { label: 'Reservas hotel', icon: 'pi pi-fw pi-calendar', to: '/business/bookings' },
                    { label: 'Serviços', icon: 'pi pi-fw pi-wrench', to: '/business/services' },
                    { label: 'Pedidos serviço', icon: 'pi pi-fw pi-calendar-plus', to: '/business/service-bookings' },
                    { label: 'Ganhos', icon: 'pi pi-fw pi-wallet', to: '/business/earnings' },
                ],
            },
        ];
    }

    return [
        {
            label: 'Kanguro',
            items: [{ label: 'Painel', icon: 'pi pi-fw pi-home', to: '/admin' }],
        },
        {
            label: 'Operações',
            items: [
                { label: 'Pedidos', icon: 'pi pi-fw pi-send', to: '/admin/orders' },
                { label: 'Excepções', icon: 'pi pi-fw pi-exclamation-triangle', to: '/admin/exceptions' },
                { label: 'Despacho', icon: 'pi pi-fw pi-map', to: '/admin/dispatch' },
                { label: 'Pagamentos', icon: 'pi pi-fw pi-wallet', to: '/admin/payments' },
                { label: 'Finanças', icon: 'pi pi-fw pi-chart-line', to: '/admin/finance' },
                { label: 'KYC motoristas', icon: 'pi pi-fw pi-id-card', to: '/admin/driver-kyc' },
                { label: 'Carteiras motoristas', icon: 'pi pi-fw pi-wallet', to: '/admin/driver-wallets' },
                { label: 'Objectos perdidos', icon: 'pi pi-fw pi-box', to: '/admin/lost-items' },
                { label: 'Veículos', icon: 'pi pi-fw pi-car', to: '/admin/vehicles' },
                { label: 'Trotinetes', icon: 'pi pi-fw pi-bolt', to: '/admin/scooters' },
            ],
        },
        {
            label: 'Catálogo',
            items: [
                { label: 'Estabelecimentos', icon: 'pi pi-fw pi-building', to: '/admin/shops' },
                { label: 'Produtos', icon: 'pi pi-fw pi-box', to: '/admin/products' },
                { label: 'Categorias', icon: 'pi pi-fw pi-tags', to: '/admin/categories' },
            ],
        },
        {
            label: 'Pessoas',
            items: [{ label: 'Utilizadores', icon: 'pi pi-fw pi-users', to: '/admin/users' }],
        },
    ];
});
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item :item="item" :index="i"></app-menu-item>
        </template>
    </ul>
</template>
