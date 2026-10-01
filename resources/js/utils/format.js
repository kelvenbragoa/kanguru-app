const STATUS_LABELS = {
    pending: 'Pendente',
    confirmed: 'Confirmado',
    assigned: 'Atribuído',
    collecting: 'A recolher',
    collected: 'Recolhido',
    in_transit: 'Em trânsito',
    delivering: 'A entregar',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
    failed: 'Falhou',
};

const STATUS_SEVERITY = {
    pending: 'warn',
    confirmed: 'info',
    assigned: 'info',
    collecting: 'info',
    collected: 'info',
    in_transit: 'info',
    delivering: 'info',
    delivered: 'success',
    cancelled: 'danger',
    failed: 'danger',
};

const ROLE_LABELS = {
    admin: 'Administrador',
    manager: 'Gerente',
    customer: 'Cliente',
    driver: 'Motorista',
    agent: 'Agente',
    shop_owner: 'Dono de loja',
};

const PAYMENT_LABELS = {
    mpesa: 'M-Pesa',
    emola: 'e-Mola',
    cash: 'Dinheiro',
    card: 'Cartão',
    pending: 'Pendente',
    completed: 'Pago',
    failed: 'Falhou',
    refunded: 'Reembolsado',
};

export function formatMt(value) {
    const n = Number(value || 0);
    return `MT ${n.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString('pt-MZ', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function roleName(user) {
    if (!user) return null;
    if (typeof user.role === 'string') return user.role;
    return user.role?.name ?? null;
}

export function roleLabel(role) {
    const name = typeof role === 'string' ? role : role?.name;
    return ROLE_LABELS[name] || name || '—';
}

export function statusLabel(status) {
    const name = typeof status === 'string' ? status : status?.name;
    return STATUS_LABELS[name] || status?.display_name || name || '—';
}

export function statusSeverity(status) {
    const name = typeof status === 'string' ? status : status?.name;
    return STATUS_SEVERITY[name] || 'secondary';
}

export function paymentLabel(value) {
    return PAYMENT_LABELS[value] || value || '—';
}

export function isStaffRole(user) {
    return ['admin', 'manager'].includes(roleName(user));
}

export function canCancelOrder(order) {
    const name = order?.order_status?.name;
    return !['collected', 'in_transit', 'delivering', 'delivered', 'cancelled', 'failed'].includes(name);
}

export function isFinalStatus(status) {
    const name = typeof status === 'string' ? status : status?.name;
    return ['delivered', 'cancelled', 'failed'].includes(name);
}

export function toDateParam(value) {
    if (!value) return null;
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return null;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export const STATUS_OPTIONS = [
    { label: 'Pendente', value: 'pending' },
    { label: 'Confirmado', value: 'confirmed' },
    { label: 'Atribuído', value: 'assigned' },
    { label: 'A recolher', value: 'collecting' },
    { label: 'Recolhido', value: 'collected' },
    { label: 'Em trânsito', value: 'in_transit' },
    { label: 'A entregar', value: 'delivering' },
    { label: 'Entregue', value: 'delivered' },
    { label: 'Cancelado', value: 'cancelled' },
    { label: 'Falhou', value: 'failed' },
];

export const TYPE_OPTIONS = [
    { label: 'Entrega', value: 'Delivery' },
    { label: 'Carga', value: 'Transporte de Carga' },
    { label: 'Mudança', value: 'Mudança' },
    { label: 'Coleta e entrega', value: 'Coleta e Entrega' },
    { label: 'Express', value: 'Express' },
];

export const PAYMENT_METHOD_OPTIONS = [
    { label: 'M-Pesa', value: 'mpesa' },
    { label: 'e-Mola', value: 'emola' },
    { label: 'Dinheiro', value: 'cash' },
    { label: 'Cartão', value: 'card' },
];

export const PAYMENT_STATUS_OPTIONS = [
    { label: 'Pendente', value: 'pending' },
    { label: 'Pago', value: 'completed' },
    { label: 'Falhou', value: 'failed' },
    { label: 'Reembolsado', value: 'refunded' },
];
