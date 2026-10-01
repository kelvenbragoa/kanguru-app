<script setup>
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useAuth } from '@/composables/useAuth';
import { apiError } from '@/api/http';

const toast = useToast();
const { user, updateProfile, changePassword } = useAuth();
const saving = ref(false);
const savingPassword = ref(false);
const profile = ref({
    name: '',
    email: '',
    phone: '',
    document: '',
    address: '',
    city: '',
});
const passwords = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const fill = () => {
    profile.value = {
        name: user.value?.name || '',
        email: user.value?.email || '',
        phone: user.value?.profile?.phone || '',
        document: user.value?.profile?.document || '',
        address: user.value?.profile?.address || '',
        city: user.value?.profile?.city || '',
    };
};

const saveProfile = async () => {
    saving.value = true;
    try {
        await updateProfile(profile.value);
        toast.add({ severity: 'success', summary: 'Perfil actualizado', life: 2500 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar.'), life: 4000 });
    } finally {
        saving.value = false;
    }
};

const savePassword = async () => {
    savingPassword.value = true;
    try {
        await changePassword(passwords.value);
        passwords.value = { current_password: '', password: '', password_confirmation: '' };
        toast.add({ severity: 'success', summary: 'Senha alterada', life: 2500 });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: apiError(error, 'Não foi possível alterar a senha.'),
            life: 4000,
        });
    } finally {
        savingPassword.value = false;
    }
};

const role = computed(() => user.value?.role?.display_name || user.value?.role?.name || '');

onMounted(fill);
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-7 card mb-0">
            <h2 class="mt-0">O meu perfil</h2>
            <p class="text-muted-color mt-0">{{ role }}</p>
            <form class="flex flex-col gap-3" @submit.prevent="saveProfile">
                <InputText v-model="profile.name" placeholder="Nome" />
                <InputText v-model="profile.email" type="email" placeholder="Email" />
                <InputText v-model="profile.phone" placeholder="Telefone" />
                <InputText v-model="profile.document" placeholder="Documento" />
                <InputText v-model="profile.address" placeholder="Morada" />
                <InputText v-model="profile.city" placeholder="Cidade" />
                <div class="flex justify-end">
                    <Button type="submit" label="Guardar perfil" :loading="saving" />
                </div>
            </form>
        </div>
        <div class="col-span-12 lg:col-span-5 card mb-0">
            <h2 class="mt-0">Alterar senha</h2>
            <form class="flex flex-col gap-3" @submit.prevent="savePassword">
                <Password v-model="passwords.current_password" placeholder="Senha actual" :feedback="false" toggleMask fluid />
                <Password v-model="passwords.password" placeholder="Nova senha" :feedback="false" toggleMask fluid />
                <Password v-model="passwords.password_confirmation" placeholder="Confirmar nova senha" :feedback="false" toggleMask fluid />
                <div class="flex justify-end">
                    <Button type="submit" label="Alterar senha" :loading="savingPassword" />
                </div>
            </form>
        </div>
    </div>
</template>
