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
const options = ref({ vehicle_types: [], vehicle_statuses: [], drivers: [] });
const form = ref({
    license_plate_number: '',
    model: '',
    color: '',
    vehicle_type_id: null,
    vehicle_status_id: 1,
    driver_id: null,
    capacity: null,
    year: null,
    description: '',
});

const load = async () => {
    loading.value = true;
    try {
        const optionsRes = await http.get('/catalog/options');
        options.value = apiPayload(optionsRes);
        if (!isEdit.value) return;
        const response = await http.get(`/vehicles/${route.params.id}`);
        const vehicle = apiPayload(response);
        form.value = {
            license_plate_number: vehicle.license_plate_number || '',
            model: vehicle.model || '',
            color: vehicle.color || '',
            vehicle_type_id: vehicle.vehicle_type_id,
            vehicle_status_id: vehicle.vehicle_status_id,
            driver_id: vehicle.driver_id,
            capacity: vehicle.capacity != null ? Number(vehicle.capacity) : null,
            year: vehicle.year,
            description: vehicle.description || '',
        };
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: isEdit.value ? 'Veículo não encontrado.' : 'Não foi possível carregar as opções.',
            life: 3000,
        });
        if (isEdit.value) {
            router.push({ name: 'vehicles.index' });
        }
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await http.put(`/vehicles/${route.params.id}`, form.value);
            toast.add({ severity: 'success', summary: 'Guardado', detail: 'Veículo actualizado.', life: 2500 });
        } else {
            await http.post('/vehicles', form.value);
            toast.add({ severity: 'success', summary: 'Criado', detail: 'Veículo adicionado à frota.', life: 2500 });
        }
        router.push({ name: 'vehicles.index' });
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
        <Button icon="pi pi-arrow-left" label="Veículos" text class="mb-3" @click="router.push({ name: 'vehicles.index' })" />
        <div class="card max-w-3xl">
            <h2 class="mt-0">{{ isEdit ? 'Editar veículo' : 'Novo veículo' }}</h2>
            <p class="text-muted-color mt-0">Motos, carros e camiões usados nas entregas e mudanças.</p>

            <form class="flex flex-col gap-4" @submit.prevent="save">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Matrícula *</label>
                        <InputText v-model="form.license_plate_number" class="w-full" :disabled="loading" placeholder="ABC-123-MP" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Modelo *</label>
                        <InputText v-model="form.model" class="w-full" :disabled="loading" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Cor *</label>
                        <InputText v-model="form.color" class="w-full" :disabled="loading" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Ano</label>
                        <InputNumber v-model="form.year" :useGrouping="false" class="w-full" :disabled="loading" />
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Tipo *</label>
                        <Select v-model="form.vehicle_type_id" :options="options.vehicle_types" optionLabel="name" optionValue="id" class="w-full" :disabled="loading" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Estado</label>
                        <Select
                            v-model="form.vehicle_status_id"
                            :options="options.vehicle_statuses"
                            optionValue="id"
                            class="w-full"
                            :disabled="loading"
                        >
                            <template #option="{ option }">{{ option.display_name || option.name }}</template>
                            <template #value="{ value, placeholder }">
                                <span v-if="value">{{ options.vehicle_statuses.find((s) => s.id === value)?.display_name }}</span>
                                <span v-else>{{ placeholder }}</span>
                            </template>
                        </Select>
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Motorista</label>
                        <Select
                            v-model="form.driver_id"
                            :options="options.drivers"
                            optionLabel="name"
                            optionValue="id"
                            showClear
                            placeholder="Sem motorista"
                            class="w-full"
                            :disabled="loading"
                        />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="font-semibold block mb-2">Capacidade (kg)</label>
                        <InputNumber v-model="form.capacity" mode="decimal" :min="0" class="w-full" :disabled="loading" />
                    </div>
                </div>
                <div>
                    <label class="font-semibold block mb-2">Descrição</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" :disabled="loading" />
                </div>
                <div class="flex gap-2 justify-end">
                    <Button type="button" label="Cancelar" severity="secondary" outlined @click="router.push({ name: 'vehicles.index' })" />
                    <Button type="submit" :label="isEdit ? 'Guardar' : 'Adicionar veículo'" :loading="saving" />
                </div>
            </form>
        </div>
    </div>
</template>
