<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, unwrapPage } from '@/api/http';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const loading = ref(false);
const vehicles = ref([]);
const total = ref(0);
const page = ref(1);
const rows = ref(15);

const statusLabel = (status) =>
    ({ available: 'Disponível', busy: 'Ocupado', maintenance: 'Manutenção', inactive: 'Inactivo' }[status?.name] || status?.display_name || status?.name || '—');

const load = async (nextPage = 1) => {
    loading.value = true;
    page.value = nextPage;
    try {
        const response = await http.get('/vehicles', { params: { page: nextPage } });
        const payload = unwrapPage(response.data);
        vehicles.value = payload.items;
        total.value = payload.total;
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    rows.value = event.rows;
    load(event.page + 1);
};

const remove = (vehicle) => {
    confirm.require({
        header: 'Apagar veículo',
        message: `Apagar ${vehicle.license_plate_number}?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Apagar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.delete(`/vehicles/${vehicle.id}`);
                toast.add({ severity: 'success', summary: 'Apagado', life: 2500 });
                await load(page.value);
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível apagar.'), life: 4000 });
            }
        },
    });
};

onMounted(() => load(1));
</script>

<template>
    <div class="card">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Veículos</h2>
                <p class="text-muted-color mt-1 mb-0">Frota para entregas, carga e mudanças.</p>
            </div>
            <Button label="Novo veículo" icon="pi pi-plus" @click="router.push({ name: 'vehicles.create' })" />
        </div>
        <DataTable
            :value="vehicles"
            :loading="loading"
            dataKey="id"
            lazy
            paginator
            :first="(page - 1) * rows"
            :rows="rows"
            :totalRecords="total"
            @page="onPage"
            responsiveLayout="scroll"
        >
            <Column field="license_plate_number" header="Matrícula" />
            <Column field="model" header="Modelo" />
            <Column field="color" header="Cor" />
            <Column header="Tipo">
                <template #body="{ data }">{{ data.vehicle_type?.name || '—' }}</template>
            </Column>
            <Column header="Estado">
                <template #body="{ data }">
                    <Tag :value="statusLabel(data.vehicle_status)" :severity="data.vehicle_status?.name === 'available' ? 'success' : 'secondary'" />
                </template>
            </Column>
            <Column header="Motorista">
                <template #body="{ data }">{{ data.driver?.name || '—' }}</template>
            </Column>
            <Column field="capacity" header="Capacidade (kg)" />
            <Column header="" style="width: 8rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="router.push({ name: 'vehicles.edit', params: { id: data.id } })" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="remove(data)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
