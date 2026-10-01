<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <h5>{{ isEditMode ? 'Editar Usuário' : 'Novo Usuário' }}</h5>

                <form @submit.prevent="submitForm">
                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="name">Nome <span class="text-red-500">*</span></label>
                                <InputText id="name" v-model="form.name" required :class="{ 'p-invalid': errors.name }" class="w-full" />
                                <small class="p-error" v-if="errors.name">{{ errors.name[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="email">Email <span class="text-red-500">*</span></label>
                                <InputText id="email" v-model="form.email" type="email" required :class="{ 'p-invalid': errors.email }" class="w-full" inputClass="w-full" />
                                <small class="p-error" v-if="errors.email">{{ errors.email[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="password">{{ isEditMode ? 'Senha (deixe em branco para não alterar)' : 'Senha *' }}</label>
                                <Password id="password" v-model="form.password" :required="!isEditMode" toggleMask :class="{ 'p-invalid': errors.password }" class="w-full" inputClass="w-full" />
                                <small class="p-error" v-if="errors.password">{{ errors.password[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="password_confirmation">{{ isEditMode ? 'Confirmar Senha' : 'Confirmar Senha *' }}</label>
                                <Password id="password_confirmation" v-model="form.password_confirmation" :required="!isEditMode && form.password" toggleMask :feedback="false" class="w-full" inputClass="w-full" />
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="role">Role <span class="text-red-500">*</span></label>
                                <Select id="role" v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Selecione uma role" required :class="{ 'p-invalid': errors.role }" class="w-full" />
                                <small class="p-error" v-if="errors.role">{{ errors.role[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="dm_id">DM <span class="text-red-500">*</span></label>
                                <Select id="dm_id" v-model="form.dm_id" :options="dms" optionLabel="name" optionValue="id" placeholder="Selecione uma DM" required :class="{ 'p-invalid': errors.dm_id }" class="w-full" />
                                <small class="p-error" v-if="errors.dm_id">{{ errors.dm_id[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="department">Departamento</label>
                                <InputText id="department" v-model="form.department" :class="{ 'p-invalid': errors.department }" class="w-full" />
                                <small class="p-error" v-if="errors.department">{{ errors.department[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field-checkbox">
                                <Checkbox id="is_active" v-model="form.is_active" :binary="true" />
                                <label for="is_active" class="ml-2">Usuário Ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-content-end mt-4">
                        <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="goBack" type="button" />
                        <Button :label="isEditMode ? 'Atualizar' : 'Salvar'" icon="pi pi-check" :loading="loading" type="submit" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const isEditMode = ref(false);
const loading = ref(false);
const errors = ref({});
const dms = ref([]);
const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    department: '',
    is_active: true
});

const roleOptions = ref([
    { label: 'Solicitante', value: 'solicitante' },
    { label: 'Armazém', value: 'armazem' },
    { label: 'Gestor', value: 'gestor' },
    { label: 'Administrador', value: 'admin' }
]);

onMounted(() => {
    if (route.params.id) {
        isEditMode.value = true;
        loadUser();
    }
    loadDm();
});

const loadUser = async () => {
    try {
        const response = await axios.get(`/api/users/${route.params.id}`);
        const userData = response.data;
        form.value = {
            name: userData.name,
            email: userData.email,
            password: '',
            password_confirmation: '',
            role: userData.role,
            department: userData.department,
            is_active: userData.is_active
        };
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar usuário', life: 3000 });
        goBack();
    }
};

const loadDm = async () => {
    try {
        const response = await axios.get(`/api/dms`);
        dms.value = response.data.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar DM', life: 3000 });
        goBack();
    }
};

const submitForm = async () => {
    loading.value = true;
    errors.value = {};

    try {
        const formData = { ...form.value };
        
        // Se for modo edição e senha estiver vazia, remover do form
        if (isEditMode.value && !formData.password) {
            delete formData.password;
            delete formData.password_confirmation;
        }

        if (isEditMode.value) {
            await axios.put(`/api/users/${route.params.id}`, formData);
            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Usuário atualizado com sucesso', life: 3000 });
        } else {
            await axios.post('/api/users', formData);
            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Usuário criado com sucesso', life: 3000 });
        }

        setTimeout(() => {
            router.push('/admin/users');
        }, 1000);
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
        toast.add({ severity: 'error', summary: 'Erro', detail: error.response?.data?.message || 'Erro ao salvar usuário', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const goBack = () => {
    router.push('/admin/users');
};
</script>
