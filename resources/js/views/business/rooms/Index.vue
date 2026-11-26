<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatMt } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(true);
const rooms = ref([]);

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get('/business/rooms');
        rooms.value = apiPayload(response) || [];
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
                <h2 class="m-0">Tipos de quarto</h2>
                <p class="text-muted-color mt-2 mb-0">Stock por quantidade — cada reserva ocupa 1 unidade por noite.</p>
            </div>
            <Button label="Novo tipo" icon="pi pi-plus" @click="router.push({ name: 'business.rooms.create' })" />
        </div>

        <DataTable :value="rooms" :loading="loading">
            <Column field="name" header="Nome" />
            <Column header="Hotel">
                <template #body="{ data }">{{ data.shop?.name || '—' }}</template>
            </Column>
            <Column field="capacity" header="Hóspedes" />
            <Column field="quantity" header="Quartos" />
            <Column header="Preço / noite">
                <template #body="{ data }">{{ formatMt(data.price_per_night) }}</template>
            </Column>
            <Column header="Activo">
                <template #body="{ data }">{{ data.is_active ? 'Sim' : 'Não' }}</template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="router.push({ name: 'business.rooms.edit', params: { id: data.id } })" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
