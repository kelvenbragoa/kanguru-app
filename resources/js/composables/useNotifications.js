import { onMounted, onUnmounted, ref } from 'vue';
import http, { apiPayload } from '@/api/http';
import { useAuth } from '@/composables/useAuth';

const notifications = ref([]);
const unread = ref(0);
const loading = ref(false);
let timer = null;
let started = 0;

async function refresh() {
    const { isAuthenticated } = useAuth();
    if (!isAuthenticated.value) {
        notifications.value = [];
        unread.value = 0;
        return;
    }
    loading.value = true;
    try {
        const [listRes, countRes] = await Promise.all([
            http.get('/notifications'),
            http.get('/notifications/unread-count'),
        ]);
        notifications.value = apiPayload(listRes) || [];
        unread.value = apiPayload(countRes)?.unread ?? 0;
    } catch {
        // silenciar erros de polling
    } finally {
        loading.value = false;
    }
}

export function useNotifications() {
    started += 1;

    onMounted(() => {
        refresh();
        if (!timer) {
            timer = window.setInterval(refresh, 20000);
        }
    });

    onUnmounted(() => {
        started = Math.max(0, started - 1);
        if (started === 0 && timer) {
            window.clearInterval(timer);
            timer = null;
        }
    });

    const markRead = async (item) => {
        if (!item.read_at) {
            await http.post(`/notifications/${item.id}/read`);
            item.read_at = new Date().toISOString();
            unread.value = Math.max(0, unread.value - 1);
        }
        return item;
    };

    const markAllRead = async () => {
        await http.post('/notifications/read-all');
        notifications.value = notifications.value.map((item) => ({
            ...item,
            read_at: item.read_at || new Date().toISOString(),
        }));
        unread.value = 0;
    };

    return {
        notifications,
        unread,
        loading,
        refresh,
        markRead,
        markAllRead,
    };
}
