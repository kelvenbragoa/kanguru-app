<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt } from '@/utils/format';

const router = useRouter();
const toast = useToast();
const loading = ref(true);
const events = ref([]);
const validateVisible = ref(false);
const qrCode = ref('');
const validating = ref(false);

const load = async () => {
    loading.value = true;
    try {
        events.value = apiPayload(await http.get('/business/events')) || [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar.'), life: 4000 });
    } finally {
        loading.value = false;
    }
};

const validate = async () => {
    validating.value = true;
    try {
        const response = await http.post('/business/tickets/validate', { qr_code: qrCode.value.trim() });
        const ticket = apiPayload(response);
        toast.add({
            severity: 'success',
            summary: 'Entrada OK',
            detail: `${ticket.user?.name || 'Cliente'} · ${ticket.lot?.name || 'Bilhete'}`,
            life: 4000,
        });
        validateVisible.value = false;
        qrCode.value = '';
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Falhou', detail: apiError(error, 'Bilhete inválido.'), life: 4000 });
    } finally {
        validating.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="card">
        <div class="flex flex-wrap justify-between gap-3 mb-4">
            <div>
                <h2 class="m-0">Eventos</h2>
                <p class="text-muted-color mt-2 mb-0">Cria eventos e lotes; valida bilhetes à porta.</p>
            </div>
            <div class="flex gap-2">
                <Button label="Validar QR" icon="pi pi-qrcode" outlined @click="validateVisible = true" />
                <Button label="Novo evento" icon="pi pi-plus" @click="router.push({ name: 'business.events.create' })" />
            </div>
        </div>

        <DataTable :value="events" :loading="loading">
            <Column field="title" header="Título" />
            <Column header="Quando">
                <template #body="{ data }">{{ formatDate(data.starts_at) }}</template>
            </Column>
            <Column field="venue" header="Local" />
            <Column header="Lotes">
                <template #body="{ data }">
                    <span v-for="lot in data.lots || []" :key="lot.id" class="block text-sm">
                        {{ lot.name }} · {{ formatMt(lot.price) }} · {{ lot.sold }}/{{ lot.quantity }}
                    </span>
                </template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="router.push({ name: 'business.events.edit', params: { id: data.id } })" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="validateVisible" header="Validar bilhete" modal :style="{ width: '28rem' }">
            <p class="text-muted-color mt-0">Introduz o código do bilhete (KNG-TKT-…).</p>
            <InputText v-model="qrCode" class="w-full" placeholder="KNG-TKT-…" />
            <template #footer>
                <Button label="Fechar" text @click="validateVisible = false" />
                <Button label="Validar" :loading="validating" @click="validate" />
            </template>
        </Dialog>
    </div>
</template>
