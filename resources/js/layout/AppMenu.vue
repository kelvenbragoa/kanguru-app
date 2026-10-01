<script setup>
import { computed } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const model = computed(() => [
    {
        label: 'YALA',
        items: [{ label: 'Painel', icon: 'pi pi-fw pi-home', to: '/admin' }],
    },
    {
        label: 'Operações',
        items: [
            { label: 'Pedidos', icon: 'pi pi-fw pi-send', to: '/admin/orders' },
            { label: 'Despacho', icon: 'pi pi-fw pi-map', to: '/admin/dispatch' },
            { label: 'Pagamentos', icon: 'pi pi-fw pi-wallet', to: '/admin/payments' },
            { label: 'Veículos', icon: 'pi pi-fw pi-car', to: '/admin/vehicles' },
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
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item :item="item" :index="i"></app-menu-item>
        </template>
    </ul>
</template>
