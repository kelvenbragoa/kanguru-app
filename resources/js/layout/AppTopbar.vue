<script setup>
import { useLayout } from '@/layout/composables/layout';
import { useAuth } from '@/composables/useAuth';
import { useNotifications } from '@/composables/useNotifications';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { formatDate, roleLabel } from '@/utils/format';
import { ref } from 'vue';

const { toggleMenu } = useLayout();
const { user, logout } = useAuth();
const { notifications, unread, markRead, markAllRead } = useNotifications();
const router = useRouter();
const toast = useToast();
const notif = ref();

const handleLogout = async () => {
    await logout();
    toast.add({
        severity: 'success',
        summary: 'Sessão terminada',
        detail: 'Até breve.',
        life: 2500,
    });
    router.push('/login');
};

const openNotification = async (item) => {
    await markRead(item);
    notif.value?.hide();
    const orderId = item.data?.order_id;
    if (orderId) {
        router.push({ name: 'orders.show', params: { id: orderId } });
    }
};
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/admin" class="layout-topbar-logo">
                <span class="kg-mark">Y</span>
                <span>YALA</span>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content flex items-center gap-2">
                    <button type="button" class="layout-topbar-action relative" @click="notif.toggle($event)">
                        <i class="pi pi-bell"></i>
                        <span v-if="unread" class="kg-badge">{{ unread > 9 ? '9+' : unread }}</span>
                    </button>
                    <button type="button" class="layout-topbar-action" @click="router.push({ name: 'profile' })">
                        <i class="pi pi-user"></i>
                        <span>Perfil</span>
                    </button>
                    <div v-if="user" class="flex flex-col items-end leading-tight pr-2">
                        <span class="font-semibold">{{ user.name }}</span>
                        <span class="text-sm text-muted-color">{{ roleLabel(user.role) }}</span>
                    </div>
                    <button type="button" class="layout-topbar-action" @click="handleLogout">
                        <i class="pi pi-sign-out"></i>
                        <span>Sair</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Popover ref="notif">
        <div class="w-20rem">
            <div class="flex justify-between items-center mb-3">
                <strong>Notificações</strong>
                <Button v-if="unread" label="Ler todas" text size="small" @click="markAllRead" />
            </div>
            <div v-if="!notifications.length" class="text-muted-color">Sem notificações.</div>
            <button
                v-for="item in notifications"
                :key="item.id"
                type="button"
                class="kg-note"
                :class="{ unread: !item.read_at }"
                @click="openNotification(item)"
            >
                <strong>{{ item.title }}</strong>
                <span>{{ item.body }}</span>
                <small>{{ formatDate(item.created_at) }}</small>
            </button>
        </div>
    </Popover>
</template>

<style scoped>
.kg-mark {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.45rem;
    background: #ffc107;
    color: #1a1400;
    display: inline-grid;
    place-items: center;
    font-weight: 900;
    margin-right: 0.5rem;
}
.kg-badge {
    position: absolute;
    top: 0.15rem;
    right: 0.15rem;
    background: #ef4444;
    color: #fff;
    border-radius: 999px;
    font-size: 0.65rem;
    min-width: 1.1rem;
    height: 1.1rem;
    display: grid;
    place-items: center;
    font-weight: 700;
}
.kg-note {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    width: 100%;
    text-align: left;
    background: transparent;
    border: 0;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.65rem 0;
    cursor: pointer;
}
.kg-note.unread {
    font-weight: 600;
}
.kg-note small {
    color: #6b7280;
}
</style>
