<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';
import ImageUpload from '@/components/ImageUpload.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isEdit = computed(() => Boolean(route.params.id));
const loading = ref(false);
const saving = ref(false);
const form = ref({
    name: '',
    type: 'restaurant',
    address: '',
    city: '',
    phone: '',
    email: '',
    description: '',
    delivery_fee: null,
    commission_rate: 0.15,
    is_featured: false,
    subscription_plan: '',
    is_paused: false,
    image_url: '',
    owner_user_id: null,
});
const cities = ['Beira', 'Maputo', 'Matola', 'Nampula', 'Quelimane', 'Tete', 'Pemba', 'Chimoio', 'Inhambane', 'Xai-Xai'];
const owners = ref([]);
const shopTypes = [
    { value: 'restaurant', label: 'Restaurante' },
    { value: 'market', label: 'Mercado' },
    { value: 'pharmacy', label: 'Farmácia' },
    { value: 'hotel', label: 'Hotel' },
    { value: 'events', label: 'Eventos' },
    { value: 'services', label: 'Serviços' },
];

const loadOwners = async () => {
    try {
        const response = await http.get('/users/role/shop_owner');
        owners.value = apiPayload(response) || [];
    } catch {
        owners.value = [];
    }
};

const load = async () => {
    loading.value = true;
    try {
        await loadOwners();
        if (!isEdit.value) return;
        const response = await http.get(`/shops/${route.params.id}`);
        const shop = apiPayload(response);
        form.value = {
            name: shop.name || '',
            type: shop.type || 'restaurant',
            address: shop.address || '',
            city: shop.city || '',
            phone: shop.phone || '',
            email: shop.email || '',
            description: shop.description || '',
            delivery_fee: shop.delivery_fee != null ? Number(shop.delivery_fee) : null,
            commission_rate: shop.commission_rate != null ? Number(shop.commission_rate) : 0.15,
            is_featured: Boolean(shop.is_featured),
            subscription_plan: shop.subscription_plan || '',
            is_paused: Boolean(shop.is_paused),
            image_url: shop.image_url || '',
            owner_user_id: shop.owner_user_id || null,
        };
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Estabelecimento não encontrado.', life: 3000 });
        router.push({ name: 'shops.index' });
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (isEdit.value) {
            await http.put(`/shops/${route.params.id}`, payload);
            toast.add({ severity: 'success', summary: 'Guardado', detail: 'Estabelecimento actualizado.', life: 2500 });
            router.push({ name: 'shops.index' });
        } else {
            const response = await http.post('/shops', payload);
            const shop = apiPayload(response);
            toast.add({ severity: 'success', summary: 'Criado', detail: 'Agora pode adicionar produtos.', life: 3000 });
            router.push({ name: 'products.create', query: { shop_id: shop.id } });
        }
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
        <Button icon="pi pi-arrow-left" label="Estabelecimentos" text class="mb-3" @click="router.push({ name: 'shops.index' })" />
        <div class="card max-w-3xl">
            <h2 class="mt-0">{{ isEdit ? 'Editar estabelecimento' : 'Novo estabelecimento' }}</h2>
            <p class="text-muted-color mt-0">Parceiro em qualquer cidade de Moçambique.</p>

            <form class="flex flex-col gap-4" @submit.prevent="save">
                <div>
                    <label for="name" class="font-semibold block mb-2">Nome *</label>
                    <InputText id="name" v-model="form.name" class="w-full" :disabled="loading" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Categoria na app *</label>
                    <Select
                        v-model="form.type"
                        :options="shopTypes"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                        :disabled="loading"
                    />
                    <small class="text-muted-color">Define em que bolha da home o parceiro aparece.</small>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-8">
                        <label for="address" class="font-semibold block mb-2">Morada *</label>
                        <InputText id="address" v-model="form.address" class="w-full" :disabled="loading" placeholder="Av. 24 de Julho" />
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <label class="font-semibold block mb-2">Cidade *</label>
                        <Select v-model="form.city" :options="cities" class="w-full" editable :disabled="loading" placeholder="Cidade" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label for="phone" class="font-semibold block mb-2">Telefone *</label>
                        <InputText id="phone" v-model="form.phone" class="w-full" :disabled="loading" placeholder="84 000 0000" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label for="email" class="font-semibold block mb-2">Email *</label>
                        <InputText id="email" v-model="form.email" type="email" class="w-full" :disabled="loading" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label for="delivery_fee" class="font-semibold block mb-2">Taxa de entrega (MT)</label>
                        <InputNumber id="delivery_fee" v-model="form.delivery_fee" mode="decimal" :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid :disabled="loading" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Comissão (0–1)</label>
                        <InputNumber v-model="form.commission_rate" mode="decimal" :min="0" :max="1" :minFractionDigits="2" :maxFractionDigits="4" fluid :disabled="loading" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="form.is_featured" :disabled="loading" />
                        <span>Destaque na app</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="form.is_paused" :disabled="loading" />
                        <span>Pausado / lotado</span>
                    </div>
                    <div class="flex-1 min-w-[12rem]">
                        <InputText v-model="form.subscription_plan" class="w-full" placeholder="Plano (ex.: pro)" :disabled="loading" />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Dono (Kanguro Business)</label>
                    <Select
                        v-model="form.owner_user_id"
                        :options="owners"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Sem dono"
                        showClear
                        class="w-full"
                        :disabled="loading"
                    />
                    <small class="text-muted-color">Utilizador com papel «Dono de loja» que gere pedidos e stock.</small>
                </div>
                <div>
                    <label for="description" class="font-semibold block mb-2">Descrição</label>
                    <Textarea id="description" v-model="form.description" rows="3" class="w-full" :disabled="loading" />
                </div>
                <div>
                    <label class="font-semibold block mb-2">Imagem</label>
                    <ImageUpload v-model="form.image_url" />
                </div>
                <div class="flex gap-2 justify-end">
                    <Button type="button" label="Cancelar" severity="secondary" outlined @click="router.push({ name: 'shops.index' })" />
                    <Button type="submit" :label="isEdit ? 'Guardar' : 'Criar e adicionar produtos'" :loading="saving" />
                </div>
            </form>
        </div>
    </div>
</template>
