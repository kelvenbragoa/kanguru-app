<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const isEdit = computed(() => Boolean(route.params.id));
const loading = ref(false);
const saving = ref(false);
const hotels = ref([]);
const form = ref({
    shop_id: null,
    name: '',
    description: '',
    capacity: 2,
    quantity: 1,
    price_per_night: null,
    free_cancel_days: 1,
    deposit_percent: 30,
    is_active: true,
});
const blockDate = ref('');
const blockUnits = ref(0);
const blockPrice = ref(null);
const blocks = ref([]);

const load = async () => {
    loading.value = true;
    try {
        const dash = await http.get('/business/dashboard');
        hotels.value = (apiPayload(dash).shops || []).filter((s) => s.type === 'hotel');
        if (!isEdit.value && hotels.value.length) {
            form.value.shop_id = hotels.value[0].id;
            return;
        }
        if (!isEdit.value) return;

        const rooms = apiPayload(await http.get('/business/rooms')) || [];
        const room = rooms.find((r) => Number(r.id) === Number(route.params.id));
        if (!room) throw new Error('missing');
        form.value = {
            shop_id: room.shop_id,
            name: room.name || '',
            description: room.description || '',
            capacity: Number(room.capacity || 2),
            quantity: Number(room.quantity || 1),
            price_per_night: room.price_per_night != null ? Number(room.price_per_night) : null,
            free_cancel_days: Number(room.free_cancel_days ?? 1),
            deposit_percent: Number(room.deposit_percent ?? 30),
            is_active: Boolean(room.is_active),
        };
        await loadBlocks();
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar.', life: 3000 });
        if (isEdit.value) router.push({ name: 'business.rooms' });
    } finally {
        loading.value = false;
    }
};

const loadBlocks = async () => {
    if (!isEdit.value) return;
    try {
        const response = await http.get(`/business/rooms/${route.params.id}/blocks`);
        blocks.value = apiPayload(response) || [];
    } catch {
        blocks.value = [];
    }
};

const saveBlock = async () => {
    if (!isEdit.value || !blockDate.value) return;
    try {
        await http.post(`/business/rooms/${route.params.id}/blocks`, {
            date: blockDate.value,
            blocked_units: blockUnits.value || 0,
            price_override: blockPrice.value,
        });
        toast.add({ severity: 'success', summary: 'Bloqueio guardado', life: 2000 });
        blockDate.value = '';
        blockUnits.value = 0;
        blockPrice.value = null;
        await loadBlocks();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error), life: 3000 });
    }
};

const removeBlock = async (id) => {
    await http.delete(`/business/rooms/${route.params.id}/blocks/${id}`);
    await loadBlocks();
};

const save = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await http.put(`/business/rooms/${route.params.id}`, form.value);
        } else {
            await http.post('/business/rooms', form.value);
        }
        toast.add({ severity: 'success', summary: 'Guardado', life: 2500 });
        router.push({ name: 'business.rooms' });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar.'), life: 4000 });
    } finally {
        saving.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div>
        <Button icon="pi pi-arrow-left" label="Quartos" text class="mb-3" @click="router.push({ name: 'business.rooms' })" />
        <div class="card max-w-3xl">
            <h2 class="mt-0">{{ isEdit ? 'Editar tipo de quarto' : 'Novo tipo de quarto' }}</h2>
            <p v-if="!hotels.length" class="text-muted-color">Não tem hotéis associados. Peça ao admin para marcar o estabelecimento como Hotel e ligar a sua conta.</p>
            <form v-else class="flex flex-col gap-4" @submit.prevent="save">
                <div>
                    <label class="font-semibold block mb-2">Hotel *</label>
                    <Select v-model="form.shop_id" :options="hotels" optionLabel="name" optionValue="id" class="w-full" :disabled="loading || isEdit" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Nome *</label>
                    <InputText v-model="form.name" class="w-full" placeholder="Duplo, Suite…" :disabled="loading" />
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Capacidade *</label>
                        <InputNumber v-model="form.capacity" :min="1" :max="20" fluid :disabled="loading" />
                    </div>
                    <div class="col-span-6">
                        <label class="font-semibold block mb-2">Quantidade *</label>
                        <InputNumber v-model="form.quantity" :min="1" :max="500" fluid :disabled="loading" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-4">
                        <label class="font-semibold block mb-2">Preço / noite (MT) *</label>
                        <InputNumber v-model="form.price_per_night" mode="decimal" :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid :disabled="loading" />
                    </div>
                    <div class="col-span-4">
                        <label class="font-semibold block mb-2">Cancelamento grátis (dias)</label>
                        <InputNumber v-model="form.free_cancel_days" :min="0" :max="60" fluid :disabled="loading" />
                    </div>
                    <div class="col-span-4">
                        <label class="font-semibold block mb-2">Sinal (%)</label>
                        <InputNumber v-model="form.deposit_percent" :min="0" :max="100" fluid :disabled="loading" />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Descrição</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" :disabled="loading" />
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_active" binary inputId="active" :disabled="loading" />
                    <label for="active">Activo no catálogo</label>
                </div>
                <Button type="submit" label="Guardar" :loading="saving" />
            </form>
        </div>

        <div v-if="isEdit" class="card max-w-3xl mt-3">
            <h3 class="mt-0">Bloqueios / preço sazonal</h3>
            <div class="grid grid-cols-12 gap-2 mb-3">
                <div class="col-span-4"><InputText v-model="blockDate" type="date" class="w-full" /></div>
                <div class="col-span-3"><InputNumber v-model="blockUnits" :min="0" placeholder="Unidades bloqueadas" fluid /></div>
                <div class="col-span-3"><InputNumber v-model="blockPrice" :min="0" mode="decimal" placeholder="Preço override" fluid /></div>
                <div class="col-span-2"><Button label="Add" class="w-full" @click="saveBlock" /></div>
            </div>
            <DataTable :value="blocks" size="small">
                <Column field="date" header="Data" />
                <Column field="blocked_units" header="Bloqueados" />
                <Column field="price_override" header="Preço" />
                <Column>
                    <template #body="{ data }">
                        <Button icon="pi pi-trash" text rounded severity="danger" @click="removeBlock(data.id)" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
