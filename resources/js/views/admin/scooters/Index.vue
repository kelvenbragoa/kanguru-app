<script setup>
import { onMounted, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload, unwrapPage } from '@/api/http';

const toast = useToast();
const confirm = useConfirm();
const loading = ref(false);
const savingPrice = ref(false);
const tab = ref(0);

const settings = ref({ unlock_fee: 10, price_per_minute: 2, minimum_amount: 20 });
const stations = ref([]);
const scooters = ref([]);
const rentals = ref([]);
const stationOptions = ref([]);

const stationDialog = ref(false);
const scooterDialog = ref(false);
const editingStation = ref(null);
const editingScooter = ref(null);
const stationForm = ref({ name: '', address: '', latitude: null, longitude: null, is_active: true });
const scooterForm = ref({ code: '', scooter_station_id: null, battery_percent: 100, status: 'available' });

const statusLabel = (status) =>
    ({ available: 'Disponível', rented: 'Alugada', maintenance: 'Manutenção' }[status] || status || '—');

const paymentLabel = (method) =>
    ({ cash: 'Dinheiro', mpesa: 'M-Pesa', emola: 'e-Mola', card: 'Cartão' }[method] || method || '—');

const loadSettings = async () => {
    const response = await http.get('/scooter-settings');
    settings.value = apiPayload(response);
};

const saveSettings = async () => {
    savingPrice.value = true;
    try {
        const response = await http.put('/scooter-settings', settings.value);
        settings.value = apiPayload(response);
        toast.add({ severity: 'success', summary: 'Preço guardado', life: 2500 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar o preço.'), life: 4000 });
    } finally {
        savingPrice.value = false;
    }
};

const loadStations = async () => {
    const response = await http.get('/scooter-stations', { params: { page: 1 } });
    const payload = unwrapPage(response.data);
    stations.value = payload.items;
    stationOptions.value = payload.items.map((item) => ({ label: item.name, value: item.id }));
};

const loadScooters = async () => {
    const response = await http.get('/scooters', { params: { page: 1 } });
    scooters.value = unwrapPage(response.data).items;
};

const loadRentals = async () => {
    const response = await http.get('/scooter-rentals', { params: { page: 1 } });
    rentals.value = unwrapPage(response.data).items;
};

const openStation = (station = null) => {
    editingStation.value = station;
    stationForm.value = station
        ? {
              name: station.name,
              address: station.address || '',
              latitude: Number(station.latitude),
              longitude: Number(station.longitude),
              is_active: Boolean(station.is_active),
          }
        : { name: '', address: '', latitude: null, longitude: null, is_active: true };
    stationDialog.value = true;
};

const saveStation = async () => {
    try {
        if (editingStation.value) {
            await http.put(`/scooter-stations/${editingStation.value.id}`, stationForm.value);
        } else {
            await http.post('/scooter-stations', stationForm.value);
        }
        stationDialog.value = false;
        toast.add({ severity: 'success', summary: 'Estação guardada', life: 2500 });
        await loadStations();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar a estação.'), life: 4000 });
    }
};

const removeStation = (station) => {
    confirm.require({
        header: 'Apagar estação',
        message: `Apagar ${station.name}?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Apagar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.delete(`/scooter-stations/${station.id}`);
                toast.add({ severity: 'success', summary: 'Apagada', life: 2500 });
                await loadStations();
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível apagar.'), life: 4000 });
            }
        },
    });
};

const openScooter = (scooter = null) => {
    editingScooter.value = scooter;
    scooterForm.value = scooter
        ? {
              code: scooter.code,
              scooter_station_id: scooter.scooter_station_id,
              battery_percent: scooter.battery_percent,
              status: scooter.status,
          }
        : { code: '', scooter_station_id: null, battery_percent: 100, status: 'available' };
    scooterDialog.value = true;
};

const saveScooter = async () => {
    try {
        if (editingScooter.value) {
            await http.put(`/scooters/${editingScooter.value.id}`, scooterForm.value);
        } else {
            await http.post('/scooters', scooterForm.value);
        }
        scooterDialog.value = false;
        toast.add({ severity: 'success', summary: 'Trotinete guardada', life: 2500 });
        await loadScooters();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar a trotinete.'), life: 4000 });
    }
};

const removeScooter = (scooter) => {
    confirm.require({
        header: 'Apagar trotinete',
        message: `Apagar ${scooter.code}?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Apagar',
        rejectLabel: 'Voltar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await http.delete(`/scooters/${scooter.id}`);
                toast.add({ severity: 'success', summary: 'Apagada', life: 2500 });
                await loadScooters();
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível apagar.'), life: 4000 });
            }
        },
    });
};

const confirmPayment = async (rental) => {
    try {
        await http.post(`/scooter-rentals/${rental.id}/confirm-payment`);
        toast.add({ severity: 'success', summary: 'Pagamento confirmado', life: 2500 });
        await loadRentals();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível confirmar.'), life: 4000 });
    }
};

const load = async () => {
    loading.value = true;
    try {
        await Promise.all([loadSettings(), loadStations(), loadScooters(), loadRentals()]);
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="card">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <div>
                    <h2 class="m-0">Trotinetes</h2>
                    <p class="text-muted-color mt-1 mb-0">Preço, estações, frota e alugueres. Sem cobrança automática na carteira.</p>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-3 mb-2">
                <div class="col-span-12 md:col-span-3">
                    <label class="font-semibold block mb-2">Desbloqueio (MT)</label>
                    <InputNumber v-model="settings.unlock_fee" class="w-full" :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="font-semibold block mb-2">Por minuto (MT)</label>
                    <InputNumber v-model="settings.price_per_minute" class="w-full" :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="font-semibold block mb-2">Mínimo (MT)</label>
                    <InputNumber v-model="settings.minimum_amount" class="w-full" :min="0" :minFractionDigits="0" :maxFractionDigits="2" />
                </div>
                <div class="col-span-12 md:col-span-3 flex align-items-end">
                    <Button label="Guardar preço" icon="pi pi-save" class="w-full" :loading="savingPrice" @click="saveSettings" />
                </div>
            </div>
        </div>

        <div class="card">
            <TabView v-model:activeIndex="tab">
                <TabPanel header="Estações">
                    <div class="flex justify-content-end mb-3">
                        <Button label="Nova estação" icon="pi pi-plus" @click="openStation()" />
                    </div>
                    <DataTable :value="stations" :loading="loading" dataKey="id" responsiveLayout="scroll">
                        <Column field="name" header="Nome" />
                        <Column field="address" header="Morada" />
                        <Column header="Disponíveis">
                            <template #body="{ data }">{{ data.available_count || 0 }} / {{ data.scooters_count || 0 }}</template>
                        </Column>
                        <Column header="Activa">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Sim' : 'Não'" :severity="data.is_active ? 'success' : 'secondary'" />
                            </template>
                        </Column>
                        <Column header="" style="width: 8rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" text rounded @click="openStation(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="removeStation(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <TabPanel header="Frota">
                    <div class="flex justify-content-end mb-3">
                        <Button label="Nova trotinete" icon="pi pi-plus" @click="openScooter()" />
                    </div>
                    <DataTable :value="scooters" :loading="loading" dataKey="id" responsiveLayout="scroll">
                        <Column field="code" header="Código" />
                        <Column header="Estação">
                            <template #body="{ data }">{{ data.station?.name || '—' }}</template>
                        </Column>
                        <Column field="battery_percent" header="Bateria %" />
                        <Column header="Estado">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="data.status === 'available' ? 'success' : data.status === 'rented' ? 'warn' : 'secondary'" />
                            </template>
                        </Column>
                        <Column header="" style="width: 8rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" text rounded @click="openScooter(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="removeScooter(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <TabPanel header="Alugueres">
                    <DataTable :value="rentals" :loading="loading" dataKey="id" responsiveLayout="scroll">
                        <Column field="code" header="Código" />
                        <Column header="Cliente">
                            <template #body="{ data }">{{ data.user?.name || '—' }}</template>
                        </Column>
                        <Column header="Trotinete">
                            <template #body="{ data }">{{ data.scooter?.code || '—' }}</template>
                        </Column>
                        <Column header="Minutos">
                            <template #body="{ data }">{{ data.duration_minutes ?? '—' }}</template>
                        </Column>
                        <Column header="Valor">
                            <template #body="{ data }">{{ data.amount != null ? `${Number(data.amount).toFixed(2)} MT` : '—' }}</template>
                        </Column>
                        <Column header="Pagamento">
                            <template #body="{ data }">{{ paymentLabel(data.payment_method) }}</template>
                        </Column>
                        <Column header="Estado">
                            <template #body="{ data }">
                                <Tag :value="data.status === 'active' ? 'Activo' : data.status === 'completed' ? 'Terminado' : data.status" :severity="data.status === 'active' ? 'warn' : 'success'" />
                            </template>
                        </Column>
                        <Column header="Pag.">
                            <template #body="{ data }">
                                <Tag :value="data.payment_status === 'confirmed' ? 'Confirmado' : 'Pendente'" :severity="data.payment_status === 'confirmed' ? 'success' : 'secondary'" />
                            </template>
                        </Column>
                        <Column header="" style="width: 4rem">
                            <template #body="{ data }">
                                <Button
                                    v-if="data.status === 'completed' && data.payment_status !== 'confirmed'"
                                    icon="pi pi-check"
                                    text
                                    rounded
                                    v-tooltip.top="'Confirmar pagamento'"
                                    @click="confirmPayment(data)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>
            </TabView>
        </div>

        <Dialog v-model:visible="stationDialog" modal :header="editingStation ? 'Editar estação' : 'Nova estação'" class="w-full md:w-30rem">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="font-semibold block mb-2">Nome *</label>
                    <InputText v-model="stationForm.name" class="w-full" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Morada</label>
                    <InputText v-model="stationForm.address" class="w-full" />
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Latitude *</label>
                        <InputNumber v-model="stationForm.latitude" class="w-full" :minFractionDigits="4" :maxFractionDigits="7" />
                    </div>
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Longitude *</label>
                        <InputNumber v-model="stationForm.longitude" class="w-full" :minFractionDigits="4" :maxFractionDigits="7" />
                    </div>
                </div>
                <div class="flex align-items-center gap-2">
                    <Checkbox v-model="stationForm.is_active" binary inputId="station-active" />
                    <label for="station-active">Estação activa</label>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="stationDialog = false" />
                <Button label="Guardar" @click="saveStation" />
            </template>
        </Dialog>

        <Dialog v-model:visible="scooterDialog" modal :header="editingScooter ? 'Editar trotinete' : 'Nova trotinete'" class="w-full md:w-30rem">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="font-semibold block mb-2">Código *</label>
                    <InputText v-model="scooterForm.code" class="w-full" placeholder="TRT-0101" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Estação *</label>
                    <Select v-model="scooterForm.scooter_station_id" :options="stationOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Escolher" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Bateria %</label>
                    <InputNumber v-model="scooterForm.battery_percent" class="w-full" :min="0" :max="100" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Estado</label>
                    <Select
                        v-model="scooterForm.status"
                        :options="[
                            { label: 'Disponível', value: 'available' },
                            { label: 'Alugada', value: 'rented' },
                            { label: 'Manutenção', value: 'maintenance' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="scooterDialog = false" />
                <Button label="Guardar" @click="saveScooter" />
            </template>
        </Dialog>
    </div>
</template>
