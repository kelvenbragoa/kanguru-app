<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <h5>{{ isEditMode ? 'Editar Departamento' : 'Novo Departamento' }}</h5>

                <form @submit.prevent="submitForm">
                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="name">Nome <span class="text-red-500">*</span></label>
                                <InputText id="name" v-model="form.name" required :class="{ 'p-invalid': errors.name }" class="w-full" />
                                <small class="p-error" v-if="errors.name">{{ errors.name[0] }}</small>
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

const form = ref({
    name: '',
});

onMounted(() => {
    if (route.params.id) {
        isEditMode.value = true;
        loadDepartment();
    }
});

const loadDepartment = async () => {
    try {
        const response = await axios.get(`/api/departments/${route.params.id}`);
        const departmentData = response.data;
        form.value = {
            name: departmentData.name,
        };
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar Departamento', life: 3000 });
        goBack();
    }
};

const submitForm = async () => {
    loading.value = true;
    errors.value = {};

    try {
        const formData = { ...form.value };
        
        if (isEditMode.value) {
            await axios.put(`/api/departments/${route.params.id}`, formData);
            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Departamento atualizado com sucesso', life: 3000 });
        } else {
            await axios.post('/api/departments', formData);
            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Departamento criado com sucesso', life: 3000 });
        }

        setTimeout(() => {
            router.push('/admin/departments');
        }, 1000);
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
        toast.add({ severity: 'error', summary: 'Erro', detail: error.response?.data?.message || 'Erro ao salvar Departamento', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const goBack = () => {
    router.push('/admin/departments');
};
</script>
