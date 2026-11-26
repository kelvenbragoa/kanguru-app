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
const providers = ref([]);
const form = ref({
    shop_id: null,
    title: '',
    category: 'casa',
    description: '',
    price: null,
    duration_minutes: 60,
    location_type: 'onsite',
    is_active: true,
});

const load = async () => {
    loading.value = true;
    try {
        const dash = await http.get('/business/dashboard');
        providers.value = (apiPayload(dash).shops || []).filter((s) => s.type === 'services');
        if (!isEdit.value && providers.value.length) {
            form.value.shop_id = providers.value[0].id;
            return;
        }
        if (!isEdit.value) return;

        const items = apiPayload(await http.get('/business/services')) || [];
        const item = items.find((e) => Number(e.id) === Number(route.params.id));
        if (!item) throw new Error('missing');
        form.value = {
            shop_id: item.shop_id,
            title: item.title || '',
            category: item.category || 'casa',
            description: item.description || '',
            price: item.price,
            duration_minutes: item.duration_minutes || 60,
            location_type: item.location_type || 'onsite',
            is_active: Boolean(item.is_active),
        };
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar.', life: 3000 });
        if (isEdit.value) router.push({ name: 'business.services' });
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await http.put(`/business/services/${route.params.id}`, form.value);
            toast.add({ severity: 'success', summary: 'Guardado', life: 2500 });
            router.push({ name: 'business.services' });
        } else {
            await http.post('/business/services', form.value);
            toast.add({ severity: 'success', summary: 'Criado', life: 2500 });
            router.push({ name: 'business.services' });
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
    <div class="card">
        <Button icon="pi pi-arrow-left" label="Serviços" text class="mb-3" @click="router.push({ name: 'business.services' })" />
        <h2 class="mt-0">{{ isEdit ? 'Editar serviço' : 'Novo serviço' }}</h2>
        <div v-if="loading">A carregar…</div>
        <div v-else class="flex flex-col gap-3 max-w-xl">
            <p v-if="!providers.length" class="text-muted-color">Não tem estabelecimento do tipo Serviços. Peça ao admin para criar e ligar a conta.</p>
            <template v-else>
                <div>
                    <label class="block mb-1">Prestador</label>
                    <Select v-model="form.shop_id" :options="providers" optionLabel="name" optionValue="id" class="w-full" :disabled="isEdit" />
                </div>
                <div>
                    <label class="block mb-1">Título</label>
                    <InputText v-model="form.title" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1">Categoria</label>
                    <InputText v-model="form.category" class="w-full" placeholder="casa, beleza, técnico…" />
                </div>
                <div>
                    <label class="block mb-1">Descrição</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" />
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6">
                        <label class="block mb-1">Preço (MT)</label>
                        <InputNumber v-model="form.price" class="w-full" :min="0" />
                    </div>
                    <div class="col-span-6">
                        <label class="block mb-1">Duração (min)</label>
                        <InputNumber v-model="form.duration_minutes" class="w-full" :min="15" />
                    </div>
                </div>
                <div>
                    <label class="block mb-1">Local</label>
                    <Select
                        v-model="form.location_type"
                        :options="[
                            { value: 'onsite', label: 'No local' },
                            { value: 'remote', label: 'À distância' },
                            { value: 'both', label: 'Ambos' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <ToggleSwitch v-model="form.is_active" />
                    <span>Activo</span>
                </div>
                <Button label="Guardar" :loading="saving" @click="save" />
            </template>
        </div>
    </div>
</template>
