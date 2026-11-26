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
const completeVisible = ref(false);
const completionCode = ref('');
const rejectReason = ref('');

const canAccept = computed(() => booking.value?.status === 'pending');
const canStart = computed(() => booking.value?.status === 'accepted');
const canComplete = computed(() => ['accepted', 'in_progress'].includes(booking.value?.status));

const load = async () => {
    loading.value = true;
    try {
        booking.value = apiPayload(await http.get(`/business/service-bookings/${route.params.id}`));
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Pedido não encontrado.', life: 3000 });
        router.push({ name: 'business.serviceBookings' });
    } finally {
        loading.value = false;
    }
};

const act = async (action, body = {}) => {
    working.value = true;
    try {
        await http.post(`/business/service-bookings/${booking.value.id}/${action}`, body);
        toast.add({ severity: 'success', summary: 'Actualizado', life: 2500 });
        completeVisible.value = false;
        completionCode.value = '';
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível.'), life: 4000 });
    } finally {
        working.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Pedidos" text class="mb-3" @click="router.push({ name: 'business.serviceBookings' })" />
        <div v-if="loading" class="card">A carregar…</div>
        <div v-else-if="booking" class="card">
            <div class="flex flex-wrap justify-between gap-3 mb-4">
                <div>
                    <h2 class="m-0">{{ booking.code }}</h2>
                    <p class="text-muted-color mt-2 mb-0">{{ booking.status_label }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="canAccept" label="Aceitar" icon="pi pi-check" :loading="working" @click="act('accept')" />
                    <Button
                        v-if="canAccept"
                        label="Recusar"
                        severity="danger"
                        outlined
                        :loading="working"
                        @click="act('reject', { reason: rejectReason || 'Prestador recusou o pedido' })"
                    />
                    <Button v-if="canStart" label="Iniciar" icon="pi pi-play" :loading="working" @click="act('start')" />
                    <Button v-if="canComplete" label="Concluir" icon="pi pi-lock" @click="completeVisible = true" />
                </div>
            </div>
            <p><strong>Serviço:</strong> {{ booking.offering?.title }}</p>
            <p><strong>Cliente:</strong> {{ booking.user?.name }}</p>
            <p><strong>Quando:</strong> {{ formatDate(booking.scheduled_at) }}</p>
            <p><strong>Morada:</strong> {{ booking.address || '—' }}</p>
            <p><strong>Notas:</strong> {{ booking.notes || '—' }}</p>
            <p><strong>Total:</strong> {{ formatMt(booking.price) }}</p>
            <div v-if="canAccept" class="mt-3">
                <label class="block mb-1">Motivo de recusa (opcional)</label>
                <InputText v-model="rejectReason" class="w-full" />
            </div>
        </div>

        <Dialog v-model:visible="completeVisible" header="Código de conclusão" modal :style="{ width: '24rem' }">
            <p class="text-muted-color mt-0">Peça o código de 4 dígitos ao cliente.</p>
            <InputText v-model="completionCode" maxlength="4" class="w-full" placeholder="4 dígitos" />
            <template #footer>
                <Button label="Fechar" text @click="completeVisible = false" />
                <Button label="Confirmar" :loading="working" @click="act('complete', { completion_code: completionCode })" />
            </template>
        </Dialog>
    </div>
</template>
