<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <h5>{{ isEditMode ? 'Editar DM' : 'Novo DM' }}</h5>

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
        loadDm();
    }
});

const loadDm = async () => {
    try {
        const response = await axios.get(`/api/dms/${route.params.id}`);
        const dmData = response.data;
        form.value = {
            name: dmData.name,
        };
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
        
        if (isEditMode.value) {
            await axios.put(`/api/dms/${route.params.id}`, formData);
            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'DM atualizado com sucesso', life: 3000 });
        } else {
            await axios.post('/api/dms', formData);
            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'DM criado com sucesso', life: 3000 });
        }

        setTimeout(() => {
            router.push('/admin/dms');
        }, 1000);
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
        toast.add({ severity: 'error', summary: 'Erro', detail: error.response?.data?.message || 'Erro ao salvar DM', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const goBack = () => {
    router.push('/admin/dms');
};
</script>
