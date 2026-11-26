import { computed, ref } from 'vue';
import http, { apiPayload, applyAuthToken, setUnauthorizedHandler, TOKEN_KEY } from '@/api/http';
import { isBusinessRole, isPortalRole, isStaffRole, roleName } from '@/utils/format';

const USER_KEY = 'auth_user';

const token = ref(localStorage.getItem(TOKEN_KEY) || null);
const user = ref(JSON.parse(localStorage.getItem(USER_KEY) || 'null'));

function persist(nextToken, nextUser) {
    token.value = nextToken || null;
    user.value = nextUser || null;
    if (nextToken) {
        localStorage.setItem(TOKEN_KEY, nextToken);
        applyAuthToken(nextToken);
    } else {
        localStorage.removeItem(TOKEN_KEY);
        applyAuthToken(null);
    }
    if (nextUser) {
        localStorage.setItem(USER_KEY, JSON.stringify(nextUser));
    } else {
        localStorage.removeItem(USER_KEY);
    }
}

function clearSession() {
    persist(null, null);
    if (
        window.location.pathname.startsWith('/admin') ||
        window.location.pathname.startsWith('/business')
    ) {
        window.location.href = '/login';
    }
}

setUnauthorizedHandler(clearSession);

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);
    const currentRole = computed(() => roleName(user.value));
    const isAdmin = computed(() => currentRole.value === 'admin');
    const isManager = computed(() => currentRole.value === 'manager');
    const isStaff = computed(() => isStaffRole(user.value));
    const isBusiness = computed(() => isBusinessRole(user.value));
    const isPortalUser = computed(() => isPortalRole(user.value));

    const login = async (email, password) => {
        try {
            const response = await http.post('/auth/login', { email, password });
            const data = apiPayload(response) || {};
            const nextToken = data.token || data.access_token || response.data?.token;
            const nextUser = data.user || data;
            if (!nextToken) {
                return {
                    success: false,
                    message: 'Login sem token. Actualize o backend / faça rebuild do portal.',
                };
            }
            const userPayload = data.user ?? (nextUser?.id ? nextUser : null);
            if (!userPayload) {
                return { success: false, message: 'Login sem dados de utilizador.' };
            }
            persist(nextToken, userPayload);
            return { success: true, user: userPayload };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Erro ao fazer login',
            };
        }
    };

    const logout = async () => {
        try {
            if (token.value) {
                await http.post('/auth/logout');
            }
        } catch {
            // Ignorar falha de rede no logout
        } finally {
            persist(null, null);
        }
    };

    const fetchUser = async () => {
        if (!token.value) return null;
        try {
            const response = await http.get('/auth/me');
            const data = apiPayload(response);
            persist(token.value, data.user);
            return data.user;
        } catch {
            persist(null, null);
            return null;
        }
    };

    const updateProfile = async (payload) => {
        const response = await http.put('/auth/profile', payload);
        const data = apiPayload(response);
        persist(token.value, data.user);
        return data.user;
    };

    const changePassword = async (payload) => {
        await http.put('/auth/change-password', payload);
    };

    return {
        token,
        user,
        isAuthenticated,
        currentRole,
        isAdmin,
        isManager,
        isStaff,
        isBusiness,
        isPortalUser,
        login,
        logout,
        fetchUser,
        updateProfile,
        changePassword,
    };
}
