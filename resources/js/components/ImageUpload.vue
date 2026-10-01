<script setup>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload } from '@/api/http';

const model = defineModel({ type: String, default: '' });
const toast = useToast();
const uploading = ref(false);
const input = ref(null);

const pick = () => input.value?.click();

const onFile = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    uploading.value = true;
    try {
        const body = new FormData();
        body.append('file', file);
        const response = await http.post('/uploads', body);
        model.value = apiPayload(response).url;
        toast.add({ severity: 'success', summary: 'Imagem carregada', life: 2000 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível carregar a imagem.'), life: 4000 });
    } finally {
        uploading.value = false;
    }
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-if="model" class="overflow-hidden rounded-xl border border-surface-200 max-w-xs">
            <img :src="model" alt="Pré-visualização" class="block w-full max-h-40 object-cover" />
        </div>
        <div class="flex gap-2">
            <input ref="input" type="file" accept="image/*" class="hidden" @change="onFile" />
            <Button type="button" :label="model ? 'Trocar imagem' : 'Carregar imagem'" icon="pi pi-upload" :loading="uploading" @click="pick" />
            <Button v-if="model" type="button" label="Remover" severity="secondary" outlined @click="model = ''" />
        </div>
        <InputText v-model="model" placeholder="ou cole um URL" class="w-full" />
    </div>
</template>
