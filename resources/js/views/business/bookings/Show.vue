<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatDate, formatMt } from '@/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(true);
const working = ref(false);
const booking = ref(null);
const checkinCode = ref('');
const qrPayload = ref('');
const checkinVisible = ref(false);

const canConfirm = computed(() => booking.value?.status === 'pending');
const canCheckIn = computed(() => booking.value?.status === 'confirmed');
const canCheckOut = computed(() => booking.value?.status === 'checked_in');
const canNoShow = computed(() => booking.value?.status === 'confirmed');
const canCancel = computed(() => ['pending', 'confirmed'].includes(booking.value?.status));

const load = async () => {
    loading.value = true;
    try {
        const response = await http.get(`/business/hotel-bookings/${route.params.id}`);
        booking.value = apiPayload(response);
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Reserva não encontrada.', life: 3000 });
        router.push({ name: 'business.bookings' });
    } finally {
        loading.value = false;
    }
};

const confirm = async () => {
    working.value = true;
    try {
        await http.post(`/business/hotel-bookings/${booking.value.id}/confirm`);
        toast.add({ severity: 'success', summary: 'Confirmada', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 4000 });
    } finally {
        working.value = false;
    }
};

const reject = async () => {
    working.value = true;
    try {
        await http.post(`/business/hotel-bookings/${booking.value.id}/reject`, { reason: 'Sem disponibilidade' });
        toast.add({ severity: 'success', summary: 'Rejeitada', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 4000 });
    } finally {
        working.value = false;
    }
};

const doCheckIn = async () => {
    working.value = true;
    try {
        await http.post(`/business/hotel-bookings/${booking.value.id}/check-in`, {
            checkin_code: checkinCode.value || undefined,
            qr_payload: qrPayload.value || undefined,
        });
        toast.add({ severity: 'success', summary: 'Check-in', life: 2500 });
        checkinVisible.value = false;
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Código incorrecto.'), life: 4000 });
    } finally {
        working.value = false;
    }
};

const doCheckOut = async () => {
    working.value = true;
    try {
        await http.post(`/business/hotel-bookings/${booking.value.id}/check-out`);
        toast.add({ severity: 'success', summary: 'Check-out', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível.'), life: 4000 });
    } finally {
        working.value = false;
    }
};

const noShow = async () => {
    working.value = true;
    try {
        await http.post(`/business/hotel-bookings/${booking.value.id}/no-show`);
        toast.add({ severity: 'success', summary: 'No-show', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 4000 });
    } finally {
        working.value = false;
    }
};

const cancel = async () => {
    working.value = true;
    try {
        await http.post(`/business/hotel-bookings/${booking.value.id}/cancel`);
        toast.add({ severity: 'success', summary: 'Cancelada', life: 2500 });
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível cancelar.'), life: 4000 });
    } finally {
        working.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Reservas" text class="mb-3" @click="router.push({ name: 'business.bookings' })" />
        <div v-if="loading" class="card">A carregar…</div>
        <div v-else-if="booking" class="card">
            <div class="flex flex-wrap justify-between gap-3 mb-4">
                <div>
                    <h2 class="m-0">{{ booking.code }}</h2>
                    <p class="text-muted-color mt-2 mb-0">{{ booking.status_label }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="canConfirm" label="Confirmar" icon="pi pi-check" :loading="working" @click="confirm" />
                    <Button v-if="canConfirm" label="Rejeitar" severity="danger" outlined :loading="working" @click="reject" />
                    <Button v-if="canCheckIn" label="Check-in" icon="pi pi-sign-in" @click="checkinVisible = true" />
                    <Button v-if="canCheckOut" label="Check-out" icon="pi pi-sign-out" :loading="working" @click="doCheckOut" />
                    <Button v-if="canNoShow" label="No-show" severity="warn" outlined :loading="working" @click="noShow" />
                    <Button v-if="canCancel" label="Cancelar" severity="danger" outlined :loading="working" @click="cancel" />
                </div>
            </div>
            <p><strong>Cliente:</strong> {{ booking.user?.name }}</p>
            <p><strong>Quarto:</strong> {{ booking.room_type?.name }}</p>
            <p><strong>Entrada:</strong> {{ formatDate(booking.check_in) }}</p>
            <p><strong>Saída:</strong> {{ formatDate(booking.check_out) }}</p>
            <p><strong>Hóspedes:</strong> {{ booking.guests }} · {{ booking.nights }} noite(s)</p>
            <p><strong>Total:</strong> {{ formatMt(booking.total_price) }}</p>
            <p v-if="booking.payment_mode === 'deposit'"><strong>Sinal:</strong> {{ formatMt(booking.deposit_amount) }}</p>
            <p><strong>Código check-in:</strong> {{ booking.checkin_code }}</p>
            <p v-if="booking.qr_payload"><strong>QR:</strong> {{ booking.qr_payload }}</p>
        </div>

        <Dialog v-model:visible="checkinVisible" header="Check-in (código ou QR)" modal :style="{ width: '28rem' }">
            <div class="flex flex-column gap-3">
                <InputText v-model="checkinCode" maxlength="4" class="w-full" placeholder="4 dígitos" />
                <InputText v-model="qrPayload" class="w-full" placeholder="Payload do QR (opcional)" />
            </div>
            <template #footer>
                <Button label="Fechar" text @click="checkinVisible = false" />
                <Button label="Confirmar" :loading="working" @click="doCheckIn" />
            </template>
        </Dialog>
    </div>
</template>
