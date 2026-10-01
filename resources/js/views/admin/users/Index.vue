<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import http, { apiError, apiPayload, unwrapPage } from '@/api/http';
import { roleLabel } from '@/utils/format';

const toast = useToast();
const loading = ref(false);
const users = ref([]);
const total = ref(0);
const page = ref(1);
const rows = ref(15);
const search = ref('');
const role = ref(null);
const dialog = ref(false);
const saving = ref(false);
const editingId = ref(null);
const roles = ref([]);
const emptyForm = () => ({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: 3,
    phone: '',
    document: '',
    address: '',
    city: '',
});
const form = ref(emptyForm());
const isEdit = computed(() => Boolean(editingId.value));

const loadRoles = async () => {
    try {
        const response = await http.get('/catalog/options');
        roles.value = (apiPayload(response).roles || []).map((item) => ({
            label: item.display_name || item.name,
            value: item.id,
        }));
    } catch {
        roles.value = [
            { label: 'Administrador', value: 1 },
            { label: 'Gerente', value: 2 },
            { label: 'Cliente', value: 3 },
            { label: 'Motorista', value: 4 },
            { label: 'Agente', value: 5 },
            { label: 'Dono de loja', value: 6 },
        ];
    }
};

const load = async (nextPage = 1) => {
    loading.value = true;
    page.value = nextPage;
    try {
        const response = await http.get('/users', {
            params: {
                page: nextPage,
                per_page: rows.value,
                ...(search.value ? { search: search.value } : {}),
                ...(role.value ? { role: role.value } : {}),
            },
        });
        const payload = unwrapPage(response.data);
        users.value = payload.items;
        total.value = payload.total;
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    rows.value = event.rows;
    load(event.page + 1);
};

const openNew = () => {
    editingId.value = null;
    form.value = emptyForm();
    dialog.value = true;
};

const openEdit = (user) => {
    editingId.value = user.id;
    form.value = {
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role_id: user.role_id || user.role?.id,
        phone: user.profile?.phone || '',
        document: user.profile?.document || '',
        address: user.profile?.address || '',
        city: user.profile?.city || '',
    };
    dialog.value = true;
};

const save = async () => {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (isEdit.value) {
            if (!payload.password) {
                delete payload.password;
                delete payload.password_confirmation;
            }
            await http.put(`/users/${editingId.value}`, payload);
            toast.add({ severity: 'success', summary: 'Guardado', life: 2500 });
        } else {
            await http.post('/users', payload);
            toast.add({ severity: 'success', summary: 'Utilizador criado', life: 2500 });
        }
        dialog.value = false;
        await load(page.value);
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: apiError(error, 'Não foi possível guardar.'), life: 4000 });
    } finally {
        saving.value = false;
    }
};

const toggle = async (row) => {
    try {
        await http.patch(`/users/${row.id}/toggle-status`);
        await load(page.value);
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível alterar o estado.', life: 3000 });
    }
};

const roleFilters = [
    { label: 'Administrador', value: 'admin' },
    { label: 'Gerente', value: 'manager' },
    { label: 'Cliente', value: 'customer' },
    { label: 'Motorista', value: 'driver' },
    { label: 'Agente', value: 'agent' },
    { label: 'Dono de loja', value: 'shop_owner' },
];

watch(role, () => load(1));
onMounted(async () => {
    await loadRoles();
    await load(1);
});
</script>

<template>
    <div class="card">
        <div class="flex flex-col gap-3 mb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="m-0">Utilizadores</h2>
                    <p class="text-muted-color mt-1 mb-0">Equipa, clientes, motoristas e donos de loja.</p>
                </div>
                <Button label="Novo utilizador" icon="pi pi-plus" @click="openNew" />
            </div>
            <div class="flex flex-col md:flex-row gap-2">
                <InputText v-model="search" placeholder="Nome, email ou telefone" class="w-full md:w-18rem" @keyup.enter="load(1)" />
                <Select v-model="role" :options="roleFilters" optionLabel="label" optionValue="value" placeholder="Perfil" showClear class="w-full md:w-14rem" />
                <Button icon="pi pi-search" @click="load(1)" />
            </div>
        </div>

        <DataTable
            :value="users"
            :loading="loading"
            dataKey="id"
            lazy
            paginator
            :first="(page - 1) * rows"
            :rows="rows"
            :totalRecords="total"
            @page="onPage"
            responsiveLayout="scroll"
        >
            <Column field="name" header="Nome" sortable />
            <Column field="email" header="Email" sortable />
            <Column header="Telefone">
                <template #body="{ data }">{{ data.profile?.phone || '—' }}</template>
            </Column>
            <Column header="Cidade">
                <template #body="{ data }">{{ data.profile?.city || '—' }}</template>
            </Column>
            <Column header="Perfil">
                <template #body="{ data }">
                    <Tag :value="roleLabel(data.role)" />
                </template>
            </Column>
            <Column header="Estado">
                <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Ativo' : 'Inactivo'" :severity="data.is_active ? 'success' : 'danger'" />
                </template>
            </Column>
            <Column header="">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button
                        :label="data.is_active ? 'Desactivar' : 'Activar'"
                        text
                        :severity="data.is_active ? 'danger' : 'success'"
                        @click="toggle(data)"
                    />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="dialog" :header="isEdit ? 'Editar utilizador' : 'Novo utilizador'" modal :style="{ width: '32rem' }">
            <div class="flex flex-col gap-3">
                <InputText v-model="form.name" placeholder="Nome" />
                <InputText v-model="form.email" type="email" placeholder="Email" />
                <InputText v-model="form.phone" placeholder="Telefone" />
                <InputText v-model="form.document" placeholder="Documento" />
                <InputText v-model="form.address" placeholder="Morada" />
                <InputText v-model="form.city" placeholder="Cidade" />
                <Select v-model="form.role_id" :options="roles" optionLabel="label" optionValue="value" class="w-full" />
                <Password v-model="form.password" :placeholder="isEdit ? 'Nova senha (opcional)' : 'Senha'" :feedback="false" toggleMask fluid />
                <Password v-model="form.password_confirmation" placeholder="Confirmar senha" :feedback="false" toggleMask fluid />
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="dialog = false" />
                <Button label="Guardar" :loading="saving" @click="save" />
            </template>
        </Dialog>
    </div>
</template>
