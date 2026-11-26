<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatMt } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(true);
const items = ref([]);

const load = async () => {
    loading.value = true;
    try {
        items.value = apiPayload(await http.get('/business/services')) || [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="card">
        <div class="flex flex-wrap justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Serviços</h2>
                <p class="text-muted-color mt-2 mb-0">Ofertas do prestador com preço e duração.</p>
            </div>
            <Button label="Novo serviço" icon="pi pi-plus" @click="router.push({ name: 'business.services.create' })" />
        </div>

        <DataTable :value="items" :loading="loading">
            <Column field="title" header="Título" />
            <Column field="category" header="Categoria" />
            <Column header="Preço">
                <template #body="{ data }">{{ formatMt(data.price) }}</template>
            </Column>
            <Column header="Duração">
                <template #body="{ data }">{{ data.duration_minutes }} min</template>
            </Column>
            <Column field="location_label" header="Local" />
            <Column header="Activo">
                <template #body="{ data }">{{ data.is_active ? 'Sim' : 'Não' }}</template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="router.push({ name: 'business.services.edit', params: { id: data.id } })" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
