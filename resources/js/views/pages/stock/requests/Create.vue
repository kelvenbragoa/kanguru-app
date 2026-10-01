<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toast />
                <h5>Nova Solicitação de Material</h5>
                
                <form @submit.prevent="submitForm">
                    <div class="grid">
                        <!-- Item -->
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="item">Item <span class="text-red-500">*</span></label>
                                <!-- <AutoComplete 
                                    v-model="form.selectedItem" 
                                    :suggestions="itemSuggestions"
                                    @complete="searchItems"
                                    field="name"
                                    optionLabel="name"
                                    placeholder="Digite o código ou nome do item"
                                    class="w-full"
                                    :class="{'p-invalid': submitted && !form.item_id}"
                                >
                                    <template #item="slotProps">
                                        <div>
                                            <strong>{{ slotProps.item.code }}</strong> - {{ slotProps.item.name }}
                                            <br/>
                                            <small class="text-500">Stock: {{ slotProps.item.current_stock }} {{ slotProps.item.unit }}</small>
                                        </div>
                                    </template>
                                </AutoComplete> -->
                                <AutoComplete 
                                    v-model="form.selectedItem" 
                                    :suggestions="itemSuggestions"
                                    @complete="searchItems"
                                    field="name"
                                    optionLabel="name"
                                    placeholder="Digite o código ou nome do item"
                                    class="w-full"
                                    inputClass="w-full"
                                    :class="{'p-invalid': submitted && !form.item_id}"
                                >
                                    <template #option="slotProps">
                                        <div>
                                            <strong>{{ slotProps.option.code }}</strong> - {{ slotProps.option.name }}
                                            <br/>
                                            <small class="text-500">
                                                Stock: {{ slotProps.option.current_stock }} {{ slotProps.option.unit }}
                                            </small>
                                        </div>
                                    </template>
                                </AutoComplete>
                                <small class="p-error" v-if="submitted && !form.item_id">Item é obrigatório.</small>
                                <Message v-if="form.selectedItem && form.selectedItem.current_stock < form.quantity" severity="warn" :closable="false" class="mt-2">
                                    Stock disponível: {{ form.selectedItem.current_stock }} {{ form.selectedItem.unit }}
                                </Message>
                            </div>
                        </div>

                        <!-- Quantidade -->
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="quantity">Quantidade <span class="text-red-500">*</span></label>
                                <InputNumber v-model="form.quantity" inputId="quantity" class="w-full" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" :class="{'p-invalid': submitted && !form.quantity}" />
                                <small class="p-error" v-if="submitted && !form.quantity">Quantidade é obrigatória.</small>
                            </div>
                        </div>

                        <!-- Referência -->
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="reference">Referência <span class="text-red-500">*</span></label>
                                <InputText v-model="form.reference" id="reference" class="w-full" :class="{'p-invalid': submitted && !form.reference}" />
                                <small class="p-error" v-if="submitted && !form.reference">Referência é obrigatória.</small>
                            </div>
                        </div>

                        <!-- Work Order -->
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="work_order">Work Order <span class="text-red-500">*</span></label>
                                <InputText v-model="form.work_order" id="work_order" class="w-full" :class="{'p-invalid': submitted && !form.work_order}" />
                                <small class="p-error" v-if="submitted && !form.work_order">Work Order é obrigatória.</small>
                            </div>
                        </div>

                        <!-- ID Equipamento -->
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="equipment_id">ID Equipamento <span class="text-red-500">*</span></label>
                                <InputText v-model="form.equipment_id" id="equipment_id" class="w-full" :class="{'p-invalid': submitted && !form.equipment_id}" />
                                <small class="p-error" v-if="submitted && !form.equipment_id">ID do Equipamento é obrigatório.</small>
                            </div>
                        </div>

                        <!-- Motivo de Substituição -->
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label for="replacement_reason">Motivo de Substituição <span class="text-red-500">*</span></label>
                                <Select v-model="form.replacement_reason" :options="replacementReasons" optionLabel="label" optionValue="value" placeholder="Selecione o motivo" class="w-full" :class="{'p-invalid': submitted && !form.replacement_reason}" />
                                <small class="p-error" v-if="submitted && !form.replacement_reason">Motivo é obrigatório.</small>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div class="col-12">
                            <div class="field">
                                <label for="notes">Observações</label>
                                <Textarea v-model="form.notes" id="notes" rows="3" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-content-end gap-2">
                        <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="router.back()" />
                        <Button label="Criar Solicitação" icon="pi pi-check" type="submit" :loading="loading" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const router = useRouter();
const toast = useToast();

const form = ref({
    item_id: null,
    selectedItem: null,
    reference: '',
    work_order: '',
    quantity: 1,
    equipment_id: '',
    replacement_reason: null,
    notes: ''
});

const itemSuggestions = ref([]);
const submitted = ref(false);
const loading = ref(false);

const replacementReasons = ref([
    { label: 'Desgaste', value: 'Desgaste' },
    { label: 'Erro de Fabrico', value: 'Erro de Fabrico' },
    { label: 'Outro', value: 'Outro' }
]);

watch(() => form.value.selectedItem, (newItem) => {
    if (newItem && newItem.id) {
        form.value.item_id = newItem.id;
    }
});

const searchItems = async (event) => {
    try {
        const response = await axios.get('/api/items/search/autocomplete', {
            params: { q: event.query }
        });
        itemSuggestions.value = response.data;
    } catch (error) {
        console.error('Erro ao buscar itens:', error);
    }
};

const submitForm = async () => {
    submitted.value = true;

    if (!form.value.item_id || !form.value.quantity || !form.value.reference || !form.value.work_order || !form.value.equipment_id || !form.value.replacement_reason) {
        toast.add({severity:'warn', summary:'Atenção', detail:'Por favor, preencha todos os campos obrigatórios', life: 3000});
        return;
    }

    loading.value = true;

    try {
        const data = {
            item_id: form.value.item_id,
            reference: form.value.reference,
            work_order: form.value.work_order,
            quantity: form.value.quantity,
            equipment_id: form.value.equipment_id,
            replacement_reason: form.value.replacement_reason,
            notes: form.value.notes
        };

        const response = await axios.post('/api/requests', data);
        
        if (response.data.warning) {
            toast.add({severity:'warn', summary:'Aviso', detail:response.data.warning, life: 5000});
        }

        toast.add({severity:'success', summary:'Sucesso', detail:'Solicitação criada com sucesso', life: 3000});
        
        setTimeout(() => {
            router.push(`/admin/stock/requests/${response.data.data.id}`);
        }, 1000);
    } catch (error) {
        toast.add({severity:'error', summary:'Erro', detail:error.response?.data?.message || 'Erro ao criar solicitação', life: 3000});
    } finally {
        loading.value = false;
    }
};
</script>
