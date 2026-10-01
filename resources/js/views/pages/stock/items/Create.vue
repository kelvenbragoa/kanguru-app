<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <h5>{{ isEditMode ? 'Editar Item' : 'Novo Item' }}</h5>
                
                <form @submit.prevent="handleSubmit">
                    <div class="grid">
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="code">Código <span class="text-red-500">*</span></label>
                                <InputText 
                                    id="code" 
                                    v-model="item.code" 
                                    :class="{'p-invalid': errors.code}"
                                    placeholder="Ex: IT001"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="errors.code">{{ errors.code[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="name">Nome <span class="text-red-500">*</span></label>
                                <InputText 
                                    id="name" 
                                    v-model="item.name" 
                                    :class="{'p-invalid': errors.name}"
                                    placeholder="Nome do item"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="errors.name">{{ errors.name[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="field">
                                <label for="description">Descrição</label>
                                <Textarea 
                                    id="description" 
                                    v-model="item.description" 
                                    rows="3"
                                    placeholder="Descrição detalhada do item"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="col-12 md:col-4">
                            <div class="field">
                                <label for="unit">Unidade <span class="text-red-500">*</span></label>
                                <Select 
                                    id="unit" 
                                    v-model="item.unit" 
                                    :options="unitOptions"
                                    :class="{'p-invalid': errors.unit}"
                                    placeholder="Selecione a unidade"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="errors.unit">{{ errors.unit[0] }}</small>
                            </div>
                        </div>

                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="category_id">Categoria <span class="text-red-500">*</span></label>
                                <Select id="category_id" v-model="item.category_id" :options="categories" optionLabel="name" optionValue="id" placeholder="Selecione uma categoria" required :class="{ 'p-invalid': errors.category_id }" class="w-full" />
                                <small class="p-error" v-if="errors.category_id">{{ errors.category_id[0] }}</small>
                            </div>
                        </div>


                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="dm_id">Categoria <span class="text-red-500">*</span></label>
                                <Select id="dm_id" v-model="item.dm_id" :options="dms" optionLabel="name" optionValue="id" placeholder="Selecione um DM" required :class="{ 'p-invalid': errors.dm_id }" class="w-full" />
                                <small class="p-error" v-if="errors.dm_id">{{ errors.dm_id[0] }}</small>
                            </div>
                        </div>


                        <!-- <div class="col-12 md:col-4">
                            <div class="field">
                                <label for="category">Categoria</label>
                                <InputText 
                                    id="category" 
                                    v-model="item.category" 
                                    placeholder="Ex: Ferramentas"
                                    class="w-full"
                                />
                            </div>
                        </div> -->

                        <div class="col-12 md:col-4">
                            <div class="field">
                                <label for="location">Localização</label>
                                <InputText 
                                    id="location" 
                                    v-model="item.location" 
                                    placeholder="Ex: Armazém A - Prateleira 3"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="col-12 md:col-4">
                            <div class="field">
                                <label for="min_stock">Stock Mínimo</label>
                                <InputNumber 
                                    id="min_stock" 
                                    v-model="item.min_stock" 
                                    :min="0"
                                    placeholder="0"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="col-12 md:col-4">
                            <div class="field">
                                <label for="max_stock">Stock Máximo</label>
                                <InputNumber 
                                    id="max_stock" 
                                    v-model="item.max_stock" 
                                    :min="0"
                                    placeholder="0"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="col-12 md:col-4">
                            <div class="field">
                                <label for="current_stock">Stock Atual</label>
                                <InputNumber 
                                    id="current_stock" 
                                    v-model="item.current_stock" 
                                    :min="0"
                                    placeholder="0"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="field-checkbox">
                                <Checkbox id="is_active" v-model="item.is_active" :binary="true" />
                                <label for="is_active" class="ml-2">Item Ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-content-end mt-4">
                        <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cancel" type="button" />
                        <Button :label="isEditMode ? 'Atualizar' : 'Criar'" icon="pi pi-check" :loading="loading" type="submit" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isEditMode = ref(false);
const loading = ref(false);
const errors = ref({});
const dms = ref([]);
const categories = ref([]);


const item = ref({
    code: '',
    name: '',
    description: '',
    unit: 'un',
    category_id: 0,
    dm_id: 0,
    location: '',
    min_stock: 0,
    max_stock: 0,
    current_stock: 0,
    is_active: true
});

const unitOptions = ref(['un', 'kg', 'l', 'm', 'caixa', 'pacote', 'rolo']);

onMounted(() => {
    loadCategories();
    loadDms();
    if (route.params.id) {
        isEditMode.value = true;
        loadItem();
    }
});

const loadItem = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/items/${route.params.id}`);
        item.value = response.data;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: 'Erro ao carregar item',
            life: 3000
        });
        router.push('/admin/stock/items');
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await axios.get(`/api/categories`);
        categories.value = response.data.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar Categoria', life: 3000 });
        cancel();
    }
};

const loadDms = async () => {
    try {
        const response = await axios.get(`/api/dms`);
        dms.value = response.data.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar DMs', life: 3000 });
        cancel();
    }
};

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};
    
    try {
        if (isEditMode.value) {
            await axios.put(`/api/items/${route.params.id}`, item.value);
            toast.add({
                severity: 'success',
                summary: 'Sucesso',
                detail: 'Item atualizado com sucesso',
                life: 3000
            });
        } else {
            await axios.post('/api/items', item.value);
            toast.add({
                severity: 'success',
                summary: 'Sucesso',
                detail: 'Item criado com sucesso',
                life: 3000
            });
        }
        router.push('/admin/stock/items');
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
            toast.add({
                severity: 'error',
                summary: 'Erro de Validação',
                detail: 'Por favor, corrija os erros no formulário',
                life: 3000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Erro',
                detail: error.response?.data?.message || 'Erro ao salvar item',
                life: 3000
            });
        }
    } finally {
        loading.value = false;
    }
};

const cancel = () => {
    router.push('/admin/stock/items');
};
</script>
