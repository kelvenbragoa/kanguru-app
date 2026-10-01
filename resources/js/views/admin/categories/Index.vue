<script setup>
import { onMounted, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';

const toast = useToast();
const confirm = useConfirm();
const loading = ref(false);
const saving = ref(false);
const categories = ref([]);
const dialog = ref(false);
const editing = ref(null);
const name = ref('');

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get('/product-categories');
        categories.value = apiPayload(response) || [];
    } finally {
        loading.value = false;
    }
};

const openNew = () => {
    editing.value = null;
    name.value = '';
    dialog.value = true;
};

const openEdit = (category) => {
    editing.value = category;
    name.value = category.name;
    dialog.value = true;
};

const save = async () => {
    saving.value = true;
    try {
        if (editing.value) {
            await http.put(`/product-categories/${editing.value.id}`, { name: name.value });
            toast.add({ severity: 'success', summary: 'Guardado', life: 2500 });
        } else {
            await http.post('/product-categories', { name: name.value });
            toast.add({ severity: 'success', summary: 'Criada', life: 2500 });
        }
        dialog.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar.'), life: 4000 });
    } finally {
        saving.value = false;
    }
};

const remove = (category) => {
    confirm.require({
        header: 'Apagar categoria',
        message: `Apagar ${category.name}?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Apagar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.delete(`/product-categories/${category.id}`);
                toast.add({ severity: 'success', summary: 'Apagada', life: 2500 });
                await load();
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível apagar.'), life: 4000 });
            }
        },
    });
};

onMounted(load);
</script>

<template>
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="m-0">Categorias</h2>
                <p class="text-muted-color mt-1 mb-0">Usadas ao criar produtos nos estabelecimentos.</p>
            </div>
            <Button label="Nova categoria" icon="pi pi-plus" @click="openNew" />
        </div>

        <DataTable :value="categories" :loading="loading" dataKey="id" responsiveLayout="scroll">
            <Column field="name" header="Nome" />
            <Column field="products_count" header="Produtos" />
            <Column header="" style="width: 8rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="remove(data)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="dialog" :header="editing ? 'Editar categoria' : 'Nova categoria'" modal :style="{ width: '24rem' }">
            <label class="font-semibold block mb-2">Nome</label>
            <InputText v-model="name" class="w-full" />
            <template #footer>
                <Button label="Cancelar" text @click="dialog = false" />
                <Button label="Guardar" :loading="saving" @click="save" />
            </template>
        </Dialog>
    </div>
</template>
