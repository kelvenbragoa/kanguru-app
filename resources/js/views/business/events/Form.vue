<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import { formatMt } from '@/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const isEdit = computed(() => Boolean(route.params.id));
const loading = ref(false);
const saving = ref(false);
const organizers = ref([]);
const lots = ref([]);
const form = ref({
    shop_id: null,
    title: '',
    category: 'concerto',
    venue: '',
    description: '',
    starts_at: null,
    ends_at: null,
    is_active: true,
});
const lotForm = ref({ name: 'Normal', price: null, quantity: 100, description: '' });
const savingLot = ref(false);

const toInputDate = (value) => {
    if (!value) return null;
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return null;
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const load = async () => {
    loading.value = true;
    try {
        const dash = await http.get('/business/dashboard');
        organizers.value = (apiPayload(dash).shops || []).filter((s) => s.type === 'events');
        if (!isEdit.value && organizers.value.length) {
            form.value.shop_id = organizers.value[0].id;
            return;
        }
        if (!isEdit.value) return;

        const events = apiPayload(await http.get('/business/events')) || [];
        const event = events.find((e) => Number(e.id) === Number(route.params.id));
        if (!event) throw new Error('missing');
        form.value = {
            shop_id: event.shop_id,
            title: event.title || '',
            category: event.category || 'concerto',
            venue: event.venue || '',
            description: event.description || '',
            starts_at: toInputDate(event.starts_at),
            ends_at: toInputDate(event.ends_at),
            is_active: Boolean(event.is_active),
        };
        lots.value = event.lots || [];
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar.', life: 3000 });
        if (isEdit.value) router.push({ name: 'business.events' });
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    saving.value = true;
    try {
        const payload = {
            ...form.value,
            starts_at: form.value.starts_at ? new Date(form.value.starts_at).toISOString() : null,
            ends_at: form.value.ends_at ? new Date(form.value.ends_at).toISOString() : null,
        };
        if (isEdit.value) {
            await http.put(`/business/events/${route.params.id}`, payload);
            toast.add({ severity: 'success', summary: 'Guardado', life: 2500 });
            await load();
        } else {
            const response = await http.post('/business/events', payload);
            const event = apiPayload(response);
            toast.add({ severity: 'success', summary: 'Criado', detail: 'Agora adiciona lotes de bilhetes.', life: 3000 });
            router.push({ name: 'business.events.edit', params: { id: event.id } });
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar.'), life: 4000 });
    } finally {
        saving.value = false;
    }
};

const addLot = async () => {
    if (!isEdit.value) return;
    savingLot.value = true;
    try {
        await http.post('/business/ticket-lots', {
            event_id: Number(route.params.id),
            ...lotForm.value,
        });
        toast.add({ severity: 'success', summary: 'Lote adicionado', life: 2500 });
        lotForm.value = { name: 'VIP', price: null, quantity: 50, description: '' };
        await load();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível criar o lote.'), life: 4000 });
    } finally {
        savingLot.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Eventos" text class="mb-3" @click="router.push({ name: 'business.events' })" />
        <div class="card max-w-3xl mb-4">
            <h2 class="mt-0">{{ isEdit ? 'Editar evento' : 'Novo evento' }}</h2>
            <p v-if="!organizers.length" class="text-muted-color">Não tem estabelecimento do tipo Eventos. Peça ao admin para criar e ligar a conta.</p>
            <form v-else class="flex flex-col gap-4" @submit.prevent="save">
                <div>
                    <label class="font-semibold block mb-2">Organizador *</label>
                    <Select v-model="form.shop_id" :options="organizers" optionLabel="name" optionValue="id" class="w-full" :disabled="loading || isEdit" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Título *</label>
                    <InputText v-model="form.title" class="w-full" :disabled="loading" />
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Categoria</label>
                        <InputText v-model="form.category" class="w-full" placeholder="concerto, cinema…" :disabled="loading" />
                    </div>
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Local</label>
                        <InputText v-model="form.venue" class="w-full" :disabled="loading" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Início *</label>
                        <InputText v-model="form.starts_at" type="datetime-local" class="w-full" :disabled="loading" />
                    </div>
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Fim</label>
                        <InputText v-model="form.ends_at" type="datetime-local" class="w-full" :disabled="loading" />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Descrição</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" :disabled="loading" />
                </div>
                <Button type="submit" label="Guardar evento" :loading="saving" />
            </form>
        </div>

        <div v-if="isEdit" class="card max-w-3xl">
            <h3 class="mt-0">Lotes de bilhetes</h3>
            <ul class="pl-4">
                <li v-for="lot in lots" :key="lot.id" class="mb-2">
                    {{ lot.name }} — {{ formatMt(lot.price) }} · {{ lot.sold }}/{{ lot.quantity }} vendidos
                </li>
            </ul>
            <div class="flex flex-col gap-3 mt-4">
                <InputText v-model="lotForm.name" placeholder="Nome do lote" class="w-full" />
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6">
                        <InputNumber v-model="lotForm.price" placeholder="Preço" mode="decimal" :min="0" :minFractionDigits="2" fluid />
                    </div>
                    <div class="col-span-6">
                        <InputNumber v-model="lotForm.quantity" placeholder="Quantidade" :min="1" fluid />
                    </div>
                </div>
                <Button label="Adicionar lote" :loading="savingLot" @click="addLot" />
            </div>
        </div>
    </div>
</template>
