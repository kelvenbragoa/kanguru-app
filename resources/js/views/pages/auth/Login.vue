<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuth } from '@/composables/useAuth';
import { isStaffRole } from '@/utils/format';
import PublicHeader from '@/components/PublicHeader.vue';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const { login, logout } = useAuth();

const email = ref('');
const password = ref('');
const loading = ref(false);

const handleLogin = async () => {
    if (!email.value || !password.value) {
        toast.add({
            severity: 'warn',
            summary: 'Atenção',
            detail: 'Preencha email e senha.',
            life: 3000,
        });
        return;
    }

    loading.value = true;
    try {
        const result = await login(email.value, password.value);
        if (!result.success) {
            toast.add({
                severity: 'error',
                summary: 'Login falhou',
                detail: result.message === 'Invalid credentials' ? 'Email ou senha incorrectos.' : result.message,
                life: 4000,
            });
            return;
        }

        if (!isStaffRole(result.user)) {
            await logout();
            toast.add({
                severity: 'info',
                summary: 'Use a app YALA',
                detail: 'Este portal é para a equipa. Clientes e motoristas entram pela aplicação.',
                life: 5000,
            });
            return;
        }

        toast.add({
            severity: 'success',
            summary: 'Bem-vindo',
            detail: `Olá, ${result.user.name}.`,
            life: 2500,
        });

        const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/admin';
        router.push(redirect);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="login-page">
        <PublicHeader />
        <div class="login-wrap">
            <div class="card">
                <div class="mark">Y</div>
                <h1>Entrar</h1>
                <p class="muted">Portal da equipa YALA</p>

                <form class="form" @submit.prevent="handleLogin">
                    <label for="email">Email</label>
                    <InputText
                        id="email"
                        v-model="email"
                        type="email"
                        placeholder=""
                        class="w-full"
                        :disabled="loading"
                        autocomplete="username"
                    />

                    <label for="password">Senha</label>
                    <Password
                        id="password"
                        v-model="password"
                        placeholder="Senha"
                        :toggleMask="true"
                        :feedback="false"
                        fluid
                        :disabled="loading"
                        autocomplete="current-password"
                    />

                    <Button type="submit" label="Entrar" class="w-full mt-2" :loading="loading" />
                </form>

                <p class="hint"></p>
                <router-link to="/" class="back">Voltar à página inicial</router-link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-page {
    min-height: 100vh;
    background: #f7faf8;
    color: #111827;
}
.login-wrap {
    display: grid;
    place-items: center;
    padding: 2rem 1rem 4rem;
}
.card {
    width: min(28rem, 100%);
    background: #fff;
    border-radius: 1.5rem;
    padding: 2rem 1.75rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
}
.mark {
    width: 3rem;
    height: 3rem;
    border-radius: 0.85rem;
    background: #36b750;
    display: grid;
    place-items: center;
    font-weight: 900;
    font-size: 1.4rem;
    margin-bottom: 1rem;
}
h1 {
    margin: 0;
    font-size: 1.75rem;
}
.muted {
    color: #6b7280;
    margin: 0.35rem 0 1.5rem;
}
.form {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}
.form label {
    font-weight: 600;
    margin-top: 0.4rem;
}
.hint {
    margin-top: 1.25rem;
    font-size: 0.85rem;
    color: #6b7280;
}
.back {
    display: inline-block;
    margin-top: 0.5rem;
    font-weight: 600;
    color: #111827;
}
</style>
